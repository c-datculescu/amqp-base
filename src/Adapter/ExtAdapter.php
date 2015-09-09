<?php

namespace Amqp\Adapter;

use Amqp\Exception;
use Amqp\Exception\ChannelException;
use Amqp\Exception\ConnectionException;
use Amqp\Exception\ExchangeException;
use Amqp\Message;
use Amqp\Message\MessageInterface;

class ExtAdapter extends AbstractAdapter
{
    /**
     * Exchanges instances
     * @var \AMQPExchange[]
     */
    protected $exchangesInstances = [];

    /**
     * Connections instances
     * @var \AMQPConnection[]
     */
    protected $connections = [];

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
            return $this->getExchange($exchangeName)->publish($message->getPayload(), $routingKey, AMQP_NOPARAM, [
                'delivery_mode' => $message->getProperties(),
                // @TODO: Add more fields
            ]);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function listen($queue, callable $callable, array $options = [])
    {
        $this->getQueue($queue)->consume(function (\AMQPEnvelope $envelope) {
            
        }, $this->getQueueFlags($options));
    }

    /**
     * @inheritdoc
     */
    public function getMessage($queue, array $options = [])
    {
        $rawMessage = $this->getQueue($queue)->get($this->getQueueFlags($options));
        return $this->convertMessage($rawMessage);
    }

    /**
     * Get queue flags
     *
     * @param array $options The queue options
     *
     * @return int
     */
    protected function getQueueFlags(array $options = [])
    {
        $optionsFlagsMapping = ['auto_ack' => AMQP_AUTOACK];
        $flags = array_values(array_intersect_key($optionsFlagsMapping, array_filter($options)));

        return array_sum($flags);
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
        if ($this->exchangesInstances[$name]) {
            return $this->exchangesInstances[$name];
        }

        $config = $this->getConfig();
        if (!isset($config['exchanges'][$name])) {
            throw new \InvalidArgumentException("Exchange '{$name}' doesn't exists.");
        }

        $exchangeConfig = $config['exchanges'][$name];
        $connectionName = $exchangeConfig['connection'];
        $connectionConfig = $config['connections'][$connectionName];

        if (!isset($this->connections[$connectionName])) {
            $conn = new \AMQPConnection(array_merge($this->defaultConfig, $connectionConfig));
            $conn->connect();

            $channel = new \AMQPChannel($conn);
            if (isset($connectionConfig['prefetch_count'])) {
                $channel->setPrefetchCount($connectionConfig['prefetch_count']);
            }

            $this->connections[$connectionName] = ['connection' => $conn, 'channel' => $channel];
        }

        $this->exchangesInstances[$name] = $exchange = new \AMQPExchange($this->connections[$connectionName]['channel']);

        if (isset($exchangeConfig['bindings'])) {
            foreach ($exchangeConfig['bindings'] as $binding) {
                $this->getExchange($binding['exchange']);
                $exchange->bind($binding['exchange'], $binding['routing_key'],
                    isset($binding['arguments']) ? $binding['arguments'] : []);
            }
        }

        if (isset($exchangeConfig['attributes'])) {
            $exchange->setArguments($exchangeConfig['attributes']);
        }

        return $exchange;
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

    }

    /**
     * @param \Exception $e
     * @return Exception|ChannelException|ConnectionException|ExchangeException|\Exception
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
            default:
                return $e;
        }
    }
}