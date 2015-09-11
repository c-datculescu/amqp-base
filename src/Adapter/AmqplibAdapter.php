<?php
/**
 * @todo implement dynamic naming for queues
 */
namespace Amqp\Adapter;

use Amqp\Message\Message;
use Amqp\Message\MessageInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqplibAdapter extends AbstractAdapter
{

    /**
     * The final processed configuration for connections, queues and exchanges
     *
     * @var array
     */
    protected $finalConfig = array(
        'connections' => array(),
        'queues' => array(),
    );

    /**
     * The currently opened channels
     *
     * @var AMQPChannel[]
     */
    protected $channels = array();

    /**
     * The current message counters
     *
     * @var array
     */
    protected $counters = array();

    /**
     * Temporarily mark all dependencies here, so we can have a refcount for all of them
     *
     * @var array
     */
    protected $dependenciesCounter = array(
        'exchanges' => array(),
        'queues'    => array(),
    );

    /**
     * @param string $exchangeName
     * @param MessageInterface $message
     * @param null $routingKey
     * @return void
     */
    public function publish($exchangeName, MessageInterface $message, $routingKey = null)
    {
        $exchangeConfig = $this->exchangeConfig($exchangeName);
        $channel = $this->channel($this->finalConfig['exchanges'][$exchangeName]['connection']);
        $this->declareExchange($channel, $exchangeName);

        $channel->basic_publish(
            $this->convertToAMQPMessage($message),
            $exchangeConfig['name'],
            $routingKey !== null ? $routingKey : null
        );
    }

    /**
     * @param MessageInterface $message
     * @return AMQPMessage
     */
    protected function convertToAMQPMessage(MessageInterface $message)
    {
        $deliveryMode = $message->getDeliveryMode();
        $properties = $message->getProperties();
        $properties['application_headers'] = new AMQPTable($message->getHeaders());
        $properties['delivery_mode'] =  $deliveryMode ?: 2; // default: durable

        return new AMQPMessage($message->getPayload(), $properties);
    }

    /**
     * @return MessageInterface
     */
    protected function convertToMessage(AMQPMessage $amqpMessage)
    {
        $properties = array(
            'content_type',
            'content_encoding',
            'app_id',
            'correlation_id',
            'delivery_tag',
            'message_id',
            'priority',
            'reply_to',
            'routing_key',
            'exchange_name',
            'timestamp',
            'type',
            'user_id'
        );

        $propertyValues = array_map(
            function ($propertyName) use ($amqpMessage) {
                if ($amqpMessage->has($propertyName)) {
                    return $amqpMessage->get($propertyName);
                }

                return false;
            },
            $properties
        );

        $message = new Message();
        $message->setPayload($amqpMessage->body)
            ->setDeliveryMode($amqpMessage->get('delivery_mode'))
            ->setHeaders($amqpMessage->get('application_headers')->getNativeData())
            ->setProperties(array_combine($properties, $propertyValues));

        return $message;
    }

    /**
     * Listen for incoming messages
     *
     * @param string $queueName  The name of the queue to be used
     * @param callable $callback The callback from userland to be used. Accepts one parameter, message
     * @param array $options     The set of options to pass to the listening [multi_ack => false]
     */
    public function listen($queueName, callable $callback, array $options = array())
    {
        $options = array_merge($this->defaultConfig['listener'], $options);

        // set the global counter
        $this->counters[$queueName] = 0;

        $queueConfig = $this->queueConfig($queueName);

        if ($options['multi_ack'] == true) {
            // acknowledge at prefetch_count / 2 if prefetch count is set
            $connectionName = $this->finalConfig['queues'][$queueName]['connection'];
            $properties = $this->connectionConfig($connectionName);

            // force acknowledgements at ceil of quality of service (channel property)
            $ackAt = ceil($properties['prefetch_count'] / 2);
        } else {
            $ackAt = 0;
        }

        $channel = $this->channel($this->finalConfig['queues'][$queueName]['connection']);

        // declare the queue
        $this->declareQueue($channel, $queueName);

        $internalCallback  = \Closure::bind(function($message) use ($queueName, $channel, $callback, $options, $ackAt) {
            $return = call_user_func($callback, $this->convertToMessage($message));
            if ($return == true) {
                if ($ackAt !== 0) {
                    $this->counters[$queueName] += 1;
                    if ($this->counters[$queueName] == $ackAt) {
                        // multiple acknowledgements
                        $channel->basic_ack($message->delivery_info['delivery_tag'], true);
                        $this->counters[$queueName] = 0;
                    }
                } else {
                    // ack one by one
                    if (isset($options['auto_ack']) && $options['auto_ack'] == false) {
                        $channel->basic_ack($message->delivery_info['delivery_tag']);
                    }
                }
            } else {
                $channel->basic_nack($message->delivery_info['delivery_tag']);
            }

            return true;
        }, $this);

        $channel->basic_consume(
            $queueConfig['name'],       // $queue
            '',                         // $consumer_tag
            false,                      // $no_local
            $options['auto_ack'],       // $no_ack
            $options['exclusive'],      // $exclusive
            false,                      // $nowait
            $internalCallback,          // $callback
            null,                       // $ticket
            $queueConfig['arguments']   // $arguments
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * Initialize a connection
     *
     * @param string $name The name of the connection configuration to be used
     *
     * @return AMQPChannel
     */
    protected function channel($name)
    {
        // check cache for channel
        if (isset($this->channels[$name])) {
            // is the connection still up?
            if ($this->channels[$name]->getConnection()->isConnected()) {
                // return it
                return $this->channels[$name];
            }
        }

        // if the connection is no longer up, remake it
        $config = $this->connectionConfig($name);
        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['login'],
            $config['password'],
            $config['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $config['connect_timeout'],
            $config['read_write_timeout'],
            null,
            $config['keepalive'],
            $config['heartbeat']
        );

        // get the channel
        $channel = $connection->channel();
        $channel->basic_qos(0, $config['prefetch_count'], false);

        $this->channels[$name] = $channel;

        return $channel;
    }

    /**
     * Declares a queue with all its dependencies
     *
     * @param AMQPChannel $channel The open channel on which the queue should be declared
     * @param string $queueName    The queue name
     *
     * @return bool
     */
    protected function declareQueue(AMQPChannel $channel, $queueName)
    {
        $this->detectCircularDependencies('queue', $queueName);

        $options = $this->queueConfig($queueName);

        // increase dependency counter
        if (!isset($this->dependenciesCounter['queues'][$queueName])) {
            $this->dependenciesCounter['queues'][$queueName] = 0;
        }
        $this->dependenciesCounter['queues'][$queueName] += 1;

        // check for bindings/dependencies
        foreach ($options['bindings'] as $bind) {
            $this->declareExchange($channel, $bind['exchange']);
            $channel->queue_bind($queueName, $bind['exchange'], $bind['routing_key']);
        }

        $result = $channel->queue_declare(
            $queueName,
            $options['passive'],        // $passive
            $options['durable'],        // $durable
            $options['exclusive'],      // $exclusive
            $options['auto_delete'],    // $auto_delete
            false,                      // $nowait
            $options['arguments'],      // $arguments
            null                        // $ticket
        );
        // remove the dependency since we reached this step
        unset($this->dependenciesCounter['queues'][$queueName]);

        return $result;
    }

    /**
     * Declare an exchange
     *
     * @param AMQPChannel $channel The channel on which the exchange needs to be declared
     * @param string $exchangeName The name of the exchange to be declared
     *
     * @return bool
     */
    protected function declareExchange(AMQPChannel $channel, $exchangeName)
    {
        $this->detectCircularDependencies('exchange', $exchangeName);

        $options = $this->exchangeConfig($exchangeName);

        if (!isset($this->dependenciesCounter['exchanges'][$exchangeName])) {
            $this->dependenciesCounter['exchanges'][$exchangeName] = 0;
        }
        $this->dependenciesCounter['exchanges'][$exchangeName] += 1;

        // check for all dependencies
        if (isset($options['bindings'])) {
            foreach ($options['bindings'] as $bind) {
                $this->declareExchange($channel, $bind['exchange']);
                $channel->exchange_bind($exchangeName, $bind['exchange'], $bind['routing_key']);
            }
        }

        $result = $channel->exchange_declare(
            $exchangeName,          // $name
            $options['type'],       // $type
            $options['passive'],    // $passive
            $options['durable'],    // $durable
            false,                  // $auto_delete
            false,                  // $internal
            false,                  // $nowait
            $options['arguments'],  // $arguments
            null                    // $ticket
        );

        unset($this->dependenciesCounter['exchanges'][$exchangeName]);
        return $result;
    }

    /**
     * Attempts to detect circular dependencies in config declarations
     *
     * @param string $type Type of the current dependency [queue, exchange]
     * @param string $name The name of the dependency
     * @throws \Exception if a cyclic dependency is detected
     */
    protected function detectCircularDependencies($type, $name)
    {
        if (isset($this->dependenciesCounter[$type][$name])) {
            if ($this->dependenciesCounter[$type][$name] > 0) {
                throw new \Exception("Circular dependencies detected!");
            }
        } else {
            $this->dependenciesCounter[$type][$name] = 0;
        }
    }

    /**
     * Retrieves and converts a generic configuration for a connection
     *
     * @param string $name The name of the connection to use
     *
     * @return array
     */
    protected function connectionConfig($name)
    {
        // if we have the connection config cached, return it
        if (isset($this->finalConfig['connections'][$name])) {
            return $this->finalConfig['connections'][$name];
        }

        if (!isset($this->config['connections'][$name])) {
            throw new \InvalidArgumentException("Connection '{$name}' doesn't exists.");
        }

        // convert the config for this connection
        $localConfig = $this->config['connections'][$name];
        if (isset($localConfig['read_timeout']) && isset($localConfig['write_timeout'])) {
            $localConfig['read_write_timeout'] = min($localConfig['read_timeout'], $localConfig['write_timeout']);
        }


        $config = array_merge($this->defaultConfig['connection'], $localConfig);

        $rwt = $config['read_write_timeout'];
        $hb = $this->defaultConfig['connection']['heartbeat'];

        // @todo investigate why the connection times out if the read_write_timeout is less than heartbeat * 2
        if ($rwt < $hb*2) {
            $config['read_write_timeout'] = $hb*2;
        }

        $this->finalConfig['connections'][$name] = $config;


        return $config;
    }

    /**
     * Returns the processed queue config
     *
     * @param string $name The configuration name for the queue
     *
     * @return array
     */
    protected function queueConfig($name)
    {
        // config exists already in the cache, return it immediately
        if (isset($this->finalConfig['queues'][$name])) {
            return $this->finalConfig['queues'][$name];
        }

        if (!isset($this->config['queues'][$name])) {
            throw new \InvalidArgumentException("Queue '{$name}' doesn't exists.");
        }

        $localConfig = $this->config['queues'][$name];
        $finalQueueConfig = array_merge($this->defaultConfig['queue'], $localConfig);

        $this->finalConfig['queues'][$name] = $finalQueueConfig;

        return $finalQueueConfig;
    }

    /**
     * Prepares the configuration for exchange
     *
     * @param string $name The name of the exchange from the config
     *
     * @return array
     */
    protected function exchangeConfig($name)
    {
        // config exists already in the cache, return it immediately
        if (isset($this->finalConfig['exchanges'][$name])) {
            return $this->finalConfig['exchanges'][$name];
        }

        if (!isset($this->config['exchanges'][$name])) {
            throw new \InvalidArgumentException("Exchange '{$name}' doesn't exists.");
        }

        $localConfig = $this->config['exchanges'][$name];
        $finalExchangeConfig = array_merge($this->defaultConfig['exchanges'], $localConfig);

        $this->finalConfig['exchanges'][$name] = $finalExchangeConfig;

        return $finalExchangeConfig;
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