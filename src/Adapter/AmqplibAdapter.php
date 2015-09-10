<?php
/**
 * @todo cache for connections
 * @todo bindings
 * @todo getMessage
 * @todo publish
 * @todo implement dynamic naming for queues
 */
namespace Amqp\Adapter;

use Amqp\Message\MessageInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqplibAdapter extends AbstractAdapter
{

    protected $defaultConfig = array(
        'connection' => array(
            'host'                  => 'localhost',
            'port'                  => 5672,
            'vhost'                 => '/',
            'login'                 => 'guest',
            'password'              => 'guest',
            'connect_timeout'       => 1,
            'read_write_timeout'    => 3,
            'heartbeat'             => 10,
            'keepalive'             => true,
            'prefetch_count'        => 3,
        ),
        'queue' => array(
            'flags'                 => array('durable'),
            'arguments'             => array(),
        ),
        'exchanges' => array(
            'flags'                 => array('durable'),
            'type'                  => 'topic',
        ),
        'listener' => array(
            'auto_ack'                  => false,
            'exclusive'                 => false,
            'multiple_acknowledgement'  => false,
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

    /**
     * Listen for incoming messages
     *
     * @param string $queueName  The name of the queue to be used
     * @param callable $callback The callback from userland to be used. Accepts one parameter, message
     * @param array $options     The set of options to pass to the listening [multiple_acknowledgement => false]
     */
    public function listen($queueName, callable $callback, array $options = array())
    {
        $options = array_merge($this->defaultConfig['listener'], $options);

        // set the global counter
        $this->counters[$queueName] = 0;

        $queueConfig = $this->queueConfig($queueName);

        if ($options['multiple_acknowledgement'] == true) {
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
        // @todo use the config for the parameters of queue_declare
        $channel->queue_declare($queueConfig['name'], false, true, false, false, false, null, null);

        $internalCallback  = \Closure::bind(function($message) use ($queueName, $channel, $callback, $options, $ackAt) {
            $return = call_user_func($callback, $message);
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