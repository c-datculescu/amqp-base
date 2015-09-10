<?php

namespace Amqp\Adapter;

use Amqp\Exception;
use Amqp\Exception\ChannelException;
use Amqp\Exception\ConnectionException;
use Amqp\Exception\ExchangeException;
use Amqp\Exception\QueueException;
use Amqp\Message;
use Amqp\Message\MessageInterface;

class ExtAdapter extends AbstractAdapter
{
    /**
     * Exchanges instances
     * @var \AMQPExchange[]
     */
    protected $exchanges = [];

    /**
     * Connections instances
     * @var \AMQPConnection[]
     */
    protected $connections = [];

    /**
     * Queues instances
     * @var \AMQPQueue[]
     */
    protected $queues = [];

    /**
     * Default connection config values
     * @var array
     */
    protected $defaultConfig = [
        'host'            => 'localhost',
        'port'            => 5672,
        'vhost'           => '/',
        'connect_timeout' => 30
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function publish($exchangeName, MessageInterface $message, $routingKey = null)
    {
        try {
            $props = $message->getProperties();
            $props['delivery_mode'] = $message->getDeliveryMode();

            return $this->getExchange($exchangeName)->publish($message->getPayload(), $routingKey, AMQP_NOPARAM, $props);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function listen($queue, callable $callback, array $options = [])
    {
        try {
            $queue = $this->getQueue($queue);
            $queue->consume(\Closure::bind(function (\AMQPEnvelope $envelope) use ($callback, $queue) {
                $result = call_user_func_array($callback, [$this->convertMessage($envelope)]);
                $queue->ack($envelope->getDeliveryTag());
            }, $this), $this->getListenFlags($options));
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
     }

    /**
     * Get listen flags
     *
     * @param array $options The queue options
     *
     * @return int
     */
    protected function getListenFlags(array $options = [])
    {
        return $this->convertOptionsToFlags([
            'auto_ack' => AMQP_AUTOACK
        ], $options);
    }

    protected function getQueueFlags(array $options = [])
    {
        return $this->convertOptionsToFlags([
            'durable'    => AMQP_DURABLE,
            'passive'    => AMQP_PASSIVE,
            'exclusive'  => AMQP_EXCLUSIVE,
            'autodelete' => AMQP_AUTODELETE
        ], array_combine($options, array_fill(0, count($options), true)));
    }

    protected function getExchangeFlags(array $options = [])
    {
        return $this->convertOptionsToFlags([
            'durable'    => AMQP_DURABLE,
            'passive'    => AMQP_PASSIVE,
        ], array_combine($options, array_fill(0, count($options), true)));
    }

    /**
     * @param array $map
     * @param array $options
     *
     * @return number
     */
    protected function convertOptionsToFlags($map = [], array $options = [])
    {
        return array_sum(array_values(array_intersect_key($map, array_filter($options))));
    }

    /**
     * Convert AMQP Envelope to internal message format
     *
     * @param \AMQPEnvelope $envelope The envelope
     *
     * @return Message
     */
    protected function convertMessage(\AMQPEnvelope $envelope) {
        $message = new Message();
        $message->setPayload($envelope->getBody())
            ->setDeliveryMode($envelope->getDeliveryMode())
            ->setHeaders($envelope->getHeaders())
            ->setProperties([
                'content_type'     => $envelope->getContentType(),
                'content_encoding' => $envelope->getContentEncoding(),
                'app_id'           => $envelope->getAppId(),
                'correlation_id'   => $envelope->getCorrelationId(),
                'delivery_tag'     => $envelope->getDeliveryTag(),
                'message_id'       => $envelope->getMessageId(),
                'priority'         => $envelope->getPriority(),
                'reply_to'         => $envelope->getReplyTo(),
                'routing_key'      => $envelope->getRoutingKey(),
                'exchange_name'    => $envelope->getExchangeName(),
                'timestamp'        => $envelope->getTimeStamp(),
                'type'             => $envelope->getType(),
                'user_id'          => $envelope->getUserId()
            ])
        ;

        return $message;
    }

    /**
     * Get exchange
     *
     * @param string $name The exchange name
     *
     * @return \AMQPExchange
     * @throws ConnectionException
     */
    protected function getExchange($name)
    {
        if (isset($this->exchanges[$name])) {
            return $this->exchanges[$name];
        }

        $config = $this->getConfig();
        if (!isset($config['exchanges'][$name])) {
            throw new \InvalidArgumentException("Exchange definition '{$name}' doesn't exists.");
        }

        $exchangeConfig = $config['exchanges'][$name];
        $connection = $this->getConnection($exchangeConfig['connection']);
        $this->exchanges[$name] = $exchange = new \AMQPExchange($connection['channel']);

        if (isset($exchangeConfig['bindings'])) {
            foreach ($exchangeConfig['bindings'] as $binding) {
                try {
                    $this->getExchange($binding['exchange']);
                } catch (\InvalidArgumentException $e) {
                }

                $exchange->bind($binding['exchange'], $binding['routing_key'],
                    isset($binding['arguments']) ? $binding['arguments'] : []);
            }
        }

        if (isset($exchangeConfig['attributes'])) {
            $exchange->setArguments($exchangeConfig['attributes']);
        }

        $exchange->setName(is_callable($exchangeConfig['name']) ? call_user_func($exchangeConfig['name']) : $exchangeConfig['name']);
        $exchange->setType(isset($exchangeConfig['type']) ? $exchangeConfig['type'] : 'topic');
        $exchange->setFlags($this->getExchangeFlags(isset($exchangeConfig['flags']) ? $exchangeConfig['flags'] : 'durable'));
        $exchange->declareExchange();

        return $exchange;
    }

    /**
     * Get connection
     *
     * @param string $name Connection name
     *
     * @return array Return connection and channel
     */
    protected function getConnection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->getConfig();
        if (!isset($config['connections'][$name])) {
            throw new \InvalidArgumentException("Connection '{$name}' doesn't exists.");
        }

        $connectionConfig = $config['connections'][$name];

        $connection = new \AMQPConnection(array_merge($this->defaultConfig, $connectionConfig));
        $connection->connect();

        $channel = new \AMQPChannel($connection);
        if (isset($connectionConfig['prefetch_count'])) {
            $channel->setPrefetchCount($connectionConfig['prefetch_count']);
        }

        return $this->connections[$name] = ['connection' => $connection, 'channel' => $channel];
    }

    /**
     * Get queue
     *
     * @param string $name The queue name
     *
     * @return \AMQPQueue
     */
    protected function getQueue($name)
    {
        if (isset($this->queues[$name])) {
            return $this->queues[$name];
        }

        $config = $this->getConfig();
        if (!isset($config['queues'][$name])) {
            throw new \InvalidArgumentException("Queue definition '{$name}' doesn't exists.");
        }

        $queueConfig = $config['queues'][$name];
        $connection = $this->getConnection($queueConfig['connection']);
        $this->queues[$name] = $queue = new \AMQPQueue($connection['channel']);

        if (isset($queueConfig['bindings'])) {
            foreach ($queueConfig['bindings'] as $binding) {
                try {
                    $this->getExchange($binding['exchange']);
                } catch (\InvalidArgumentException $e) {}

                $queue->bind($binding['exchange'], $binding['routing_key'],
                    isset($binding['arguments']) ? $binding['arguments'] : []);
            }
        }

        if (isset($queueConfig['attributes'])) {

            if (isset($queueConfig['attributes']['x-dead-letter-exchange'])) {
                try {
                    $this->getExchange($queueConfig['attributes']['x-dead-letter-exchange']);
                } catch (\InvalidArgumentException $e) {}
            }

            $queue->setArguments($queueConfig['attributes']);
        }

        $queue->setFlags($this->getQueueFlags(isset($queueConfig['flags']) ? $queueConfig['flags'] : 'durable'));
        $queue->setName(is_callable($queueConfig['name']) ? call_user_func($queueConfig['name']) : $queueConfig['name']);
        $queue->declareQueue();

        return $queue;
    }

    /**
     * Convert AMQP extension exception to internal exception
     *
     * @param \Exception $e
     *
     * @return Exception|ChannelException|ConnectionException|ExchangeException|QueueException|\Exception
     */
    protected function convertException(\Exception $e)
    {
        switch(get_class($e)) {
            case 'AMQPException':
                return new Exception($e->getMessage(), $e->getCode(), $e);
            case 'AMQPConnectionException':
                return new ConnectionException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPChannelException':
                return new ChannelException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPExchangeException':
                return new ExchangeException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPQueueException':
                return new QueueException($e->getMessage(), $e->getCode(), $e);
            default:
                return $e;
        }
    }
}