<?php
namespace Amqp\Adapter;

use Amqp\Message\MessageInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqplibAdapter extends AbstractAdapter
{

    protected $defaultConfig = array(
        'connection' => array(
            'host' => 'localhost',
            'port' => 5672,
            'vhost' => '/',
            'login' => 'guest',
            'password' => 'guest',
            'connect_timeout' => 1,
            'read_write_timeout' => 0,
            'heartbeat' => 10,
            'keepalive' => true,
            'prefetch_count' => 3,
        ),
        'queue' => array(
            'flags' => array('durable')
        ),
        'exchanges' => array(
            'flags' => array('durable'),
            'type' => 'topic',
        ),
    );

    /**
     * The final processed configuration for connections, queues and exchanges
     *
     * @var array
     */
    protected $finalConfig = array(
        'connections' => array()
    );

    /**
     * The currently opened channels
     *
     * @var array
     */
    protected $channels = array();

    /**
     * The current message counters
     *
     * @var array
     */
    protected $counters = array();


    public function publish($exchangeName, MessageInterface $message, $routingKey = null)
    {

    }

    public function listen($queueName, callable $callback, array $options = array())
    {
        // set the global counter
        $this->counters[$queueName] = 0;

        $queueConfig = $this->finalConfig['queues'][$queueName];

        // acknowledge at prefetch_count / 2 if prefetch count is set
        $connectionName = $this->finalConfig['queues'][$queueName]['connection'];
        $properties = $this->connectionConfig($connectionName);
        $ackAt = ceil($properties['prefetch_count']/2);

        $channel = $this->channel($this->finalConfig['queues'][$queueName]['connection']);

        $callback  = \Closure::bind(function($message) use ($queueName, $channel, $callback, $options, $ackAt) {
            $return = $callback($message);
            if ($return == true) {
                $this->counters[$queueName] += 1;
            }
        }, $this);

        $channel->basic_consume(
            $queueConfig['name'],       // $queue
            '',                         // $consumer_tag
            false,                      // $no_local
            $options['auto_ack'],       // $no_ack
            $options['exclusive'],      // $exclusive
            false,                      // $nowait
            $callback,                  // $callback
            null,                       // $ticket
            $queueConfig['arguments']   // $arguments
        );
    }

    public function callback($message)
    {

    }

    public function getMessage($queue, array $options = array())
    {

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
     * Retrieves and converts a generic configuration for a connection
     *
     * @todo if config is already parsed, return it directly
     *
     * @param string $name The name of the connection to use
     *
     * @return array
     */
    protected function connectionConfig($name)
    {
        if (!isset($this->config['connections'][$name])) {
            throw new \InvalidArgumentException("Connection '{$name}' doesn't exists.");
        }

        // convert the config for this connection
        $localConfig = $this->config['connections'][$name];
        if (isset($localConfig['read_timeout']) && isset($localConfig['write_timeout'])) {
            $localConfig['read_write_timeout'] = min($localConfig['read_timeout'], $localConfig['write_timeout']);
        }

        $config = array_merge($this->defaultConfig['connection'], $localConfig);
        $this->finalConfig['connections'][$name] = $config;


        return $config;
    }

    /**
     * Returns the processed queue config
     *
     * @todo if config is already parsed, return it directly
     *
     * @param string $name The configuration name for the queue
     *
     * @return array
     */
    protected function queueConfig($name)
    {
        if (!isset($this->config['queues'][$name])) {
            throw new \InvalidArgumentException("Queue '{$name}' doesn't exists.");
        }

        $localConfig = $this->config['queues'][$name];
        $finalQueueConfig = array_merge($this->defaultConfig['queue'], $localConfig);

        $this->finalConfig['queues'][$name] = $finalQueueConfig;

        return $finalQueueConfig;
    }
}