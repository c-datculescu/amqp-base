<?php

namespace Amqp\Adapter;

use Amqp\Message\MessageInterface;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PhpAmqpLibAdapter implements AdapterInterface
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
        'host'               => 'localhost',
        'port'               => 5672,
        'vhost'              => '/',
        'connect_timeout'    => 30,
        'insist'             => false,
        'login_method'       => 'AMQPLAIN',
        'login_response'     => null,
        'locale'             => 'en_US',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'context'            => null,
        'keepalive'          => false,
        'heartbeat'          => 0,
        'prefetch_size'      => 0,
        'prefetch_count'     => 0,
        'global'             => false,
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
            $connectionConfig = array_merge($this->defaultConfig, $connectionConfig);

            $connection = new AMQPStreamConnection(
                $connectionConfig['host'],
                $connectionConfig['port'],
                $connectionConfig['user'],
                $connectionConfig['vhost'],
                $connectionConfig['insist'],
                $connectionConfig['login_method'],
                $connectionConfig['login_response'],
                $connectionConfig['locale'],
                $connectionConfig['connect_timeout'],
                $connectionConfig['read_write_timeout'],
                $connectionConfig['context'],
                $connectionConfig['keepalive'],
                $connectionConfig['heartbeat']
            );

            $channel = $connection->channel();

            if (isset($connectionConfig['prefetch_count'])) {
                $channel->basic_qos(0, $connectionConfig['prefetch_count'], false);
            }

            $this->connections[$connectionName] = [
                'connection' => $connection,
                'channel' => $channel
            ];
        }

        $isFlagSet = function ($flag) use ($exchangeConfig) {
            return isset($exchangeConfig['flags']) && in_array($flag, $exchangeConfig['flags']);
        };

        $exchange = $channel->exchange_declare(
            $name,
            $type        = isset($exchangeConfig['type']) ? $exchangeConfig['type'] : 'topic',
            $passive     = $isFlagSet(4),
            $durable     = $isFlagSet(2),
            $auto_delete = $isFlagSet(16),
            $internal    = $isFlagSet(32),
            $nowait      = $isFlagSet(8192),
            $arguments   = isset($exchangeConfig['arguments']) ? $exchangeConfig['arguments'] : null
        );

        $this->exchangesInstances[$name] = $exchange;

        if (isset($exchangeConfig['bindings'])) {
            foreach ($exchangeConfig['bindings'] as $binding) {
                $channel->exchange_declare($binding['exchange']);
                $channel->exchange_bind(
                    $binding['exchange'],
                    $name,
                    $binding['routing_key'],
                    false,
                    isset($binding['arguments']) ? $binding['arguments'] : []
                );
            }
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
            case 'PhpAmqpLib\Exception\AMQPException':
                return new Exception($e->getMessage(), $e->getCode(), $e);
            case 'PhpAmqpLib\Exception\AMQPProtocolConnectionException':
                return new ConnectionException($e->getMessage(), $e->getCode(), $e);
            case 'PhpAmqpLib\Exception\AMQPProtocolChannelException':
                return new ChannelException($e->getMessage(), $e->getCode(), $e);
            default:
                return $e;
        }
    }
}