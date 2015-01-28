<?php
/**
 * Class capable of returning any of the components defined in the amqp configuration file
 * The components are cached so that we do not create a connection/channel/queue/exchange every time
 * but rather reuse the currently existing ones that have been defined already.
 *
 * @author Cristian Datculescu <cristian.datculescu@gmail.com>
 */
namespace Amqp\Base\Builder;

use \AMQPConnection,
    \AMQPChannel,
    \AMQPQueue,
    \AMQPExchange;

class Amqp implements Interfaces\Amqp
{
    /**
     * @var array
     */
    protected $amqpConfiguration = array();

    /**
     * @var AMQPConnection[]
     */
    protected $connections = array();

    /**
     * @var AMQPChannel[]
     */
    protected $channels = array();

    /**
     * @var AMQPQueue[]
     */
    protected $queues = array();

    /**
     * @var AMQPExchange[]
     */
    protected $exchanges = array();

    /**
     * Registers all the unresolved dependencies, looking for dependencies which are cyclic
     * @var array
     */
    protected $cyclicLoggers = array(
        'queues'    => array(),
        'exchanges' => array(),
    );

    /**
     * @param array $amqpConfiguration The configuration for the queues/exchanges/channels/connections
     */
    public function __construct(array $amqpConfiguration)
    {
        $this->amqpConfiguration = $amqpConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function connection($connectionName)
    {
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        // retrieve the connection information
        if (!isset($this->amqpConfiguration['connection'][$connectionName])) {
            throw new Exception("Could not find connection definition!", 404);
        }

        $configuration = $this->amqpConfiguration['connection'][$connectionName];

        // because of a issue in AMQP adapter, the setConnectTimeout is not exposed
        // via the public interface.
        // this option needs to be passed to constructor
        $tempConfig = array();
        $tempConfig['connect_timeout'] = $configuration['connectTimeout'];

        // initialize the connection
        $connection = new AMQPConnection($tempConfig);

        $hosts = $configuration['host'];
        // choose random host to simulate "load balancing"
        $host = $hosts[array_rand($hosts)];
        $connection->setHost($host);

        $connection->setPort($configuration['port']);
        $connection->setLogin($configuration['login']);
        $connection->setPassword($configuration['password']);
        $connection->setVhost($configuration['vhost']);
        $connection->setReadTimeout($configuration['readTimeout']);
        $connection->setWriteTimeout($configuration['writeTimeout']);
        $connection->connect();

        $this->connections[$connectionName] = $connection;

        return $connection;
    }


    /**
     * {@inheritdoc}
     */
    public function channel($channelName)
    {
        if (isset($this->channels[$channelName])) {
            return $this->channels[$channelName];
        }

        // retrieve the connection information
        if (!isset($this->amqpConfiguration['channel'][$channelName])) {
            throw new Exception("Could not find channel definition!", 404);
        }

        $configuration = $this->amqpConfiguration['channel'][$channelName];

        $channel = new AMQPChannel($this->connection($configuration['connection']));

        if (isset($configuration['count']) && isset($configuration['size'])) {
            $channel->qos($configuration['size'], $configuration['count']);
        } else {
            if (isset($configuration['count'])) {
                $channel->setPrefetchCount($configuration['count']);
            }
            if (isset($configuration['size'])) {
                $channel->setPrefetchSize($configuration['size']);
            }
        }

        $this->channels[$channelName] = $channel;

        return $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function queue($queueName, $initDependencies = true)
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }

        // initialize the queue
        if (!isset($this->amqpConfiguration['queue'][$queueName])) {
            throw new Exception('Could not find queue definition!', 404);
        }

        $configuration = $this->amqpConfiguration['queue'][$queueName];

        if (!isset($this->cyclicLoggers['queues'][$queueName])) {
            $this->cyclicLoggers['queues'][$queueName] = 1;
        } else {
            $this->cyclicLoggers['queues'][$queueName]++;
        }

        if (isset($configuration['dependencies']) && $initDependencies == true) {
            $refCount = $this->cyclicLoggers['queues'][$queueName];
            if ($refCount > 1) {
                throw new Exception('Cyclic dependencies detected for queue ' . $queueName);
            }
            $this->initDependencies($configuration['dependencies']);
        }

        $queue = new AMQPQueue($this->channel($configuration['channel']));

        $queue->setName(isset($configuration['name']) ? $this->getName($configuration['name']) : $queueName);
        $queue->setFlags($this->buildBitmask($configuration['flags']));
        if (isset($configuration['arguments'])) {
            $queue->setArguments($this->getQueueProperties($configuration['arguments']));
        }
        $queue->declareQueue();

        // get the bindings and apply them
        if (isset($configuration['bindings'])) {
            $bindings = $configuration['bindings'];
            foreach ($bindings as $binding) {
                if (isset($binding['arguments'])) {
                    $arguments = $binding['arguments'];
                } else {
                    $arguments = array();
                }
                $queue->bind($binding['exchange'], $binding['routingKey'], $arguments);
            }
        }

        $this->queues[$queueName] = $queue;

        if (isset($this->cyclicLoggers['queues'][$queueName])) {
            $this->cyclicLoggers['queues'][$queueName]--;
        }

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function exchange($exchangeName, $initDependencies = true)
    {
        if (isset($this->exchanges[$exchangeName])) {
            return $this->exchanges[$exchangeName];
        }

        // initialize the exchange
        if (!isset($this->amqpConfiguration['exchange'][$exchangeName])) {
            throw new Exception('Could not find exchange definition!', 404);
        }

        $configuration = $this->amqpConfiguration['exchange'][$exchangeName];

        if (!isset($this->cyclicLoggers['exchanges'][$exchangeName])) {
            $this->cyclicLoggers['exchanges'][$exchangeName] = 1;
        } else {
            $this->cyclicLoggers['exchanges'][$exchangeName]++;
        }

        if (isset($configuration['dependencies']) && $initDependencies == true) {
            $refCount = $this->cyclicLoggers['exchanges'][$exchangeName];
            if ($refCount > 1) {
                throw new Exception('Cyclic dependencies detected for exchange ' . $exchangeName);
            }
            $this->initDependencies($configuration['dependencies']);
        }

        $exchange = new AMQPExchange($this->channel($configuration['channel']));

        // if we need the default exchange, retrieve it without doing all the operations.
        if (isset($configuration['isDefault'])) {
            $exchange->setName('');
            $this->exchanges['default'] = $exchange;

            return $exchange;
        }

        $exchange->setName(isset($configuration['name']) ? $this->getName($configuration['name']) : $exchangeName);

        if (isset($configuration['flags'])) {
            $exchange->setFlags($this->buildBitmask($configuration['flags']));
        }
        // alternate exchange
        if (isset($configuration['ae'])) {
            if (!isset($configuration['arguments'])) {
                $configuration['arguments'] = array();
            }
            $configuration['arguments']['alternate-exchange'] = $configuration['ae'];
        }
        if (isset($configuration['arguments'])) {
            $exchange->setArguments($configuration['arguments']);
        }

        if (isset($configuration['type'])) {
            $exchange->setType($this->getConstant($configuration['type']));
        }

        $exchange->declareExchange();

        $this->exchanges[$exchangeName] = $exchange;

        if (isset($this->cyclicLoggers['exchanges'][$exchangeName])) {
            $this->cyclicLoggers['exchanges'][$exchangeName]--;
        }

        return $exchange;
    }

    /**
     * Returns a constant value if is defined
     *
     * @param string $constantName
     * @return mixed
     * @throws Exception If the constant is not defined
     */
    protected function getConstant($constantName)
    {
        if (defined($constantName)) {
            return constant($constantName);
        }

        throw new Exception('Cannot locate value of constant ' . $constantName, 500);
    }

    /**
     * Maps queue properties on actual supported properties in rabbitmq
     *
     * @param array $arguments The list of arguments to map
     *
     * @return array
     */
    protected function getQueueProperties(array $arguments)
    {
        $ret = array();
        if (isset($arguments['message-ttl'])) {
            $ret['x-message-ttl'] = $arguments['message-ttl'];
        }

        if (isset($arguments['expires'])) {
            $ret['x-expires'] = $arguments['expires'];
        }

        if (isset($arguments['dl-exchange'])) {
            $ret['x-dead-letter-exchange'] = $arguments['dl-exchange'];
        }

        if (isset($arguments['dl-routingKey'])) {
            $ret['x-dead-letter-routing-key'] = $arguments['dl-routingKey'];
        }

        if (isset($arguments['max-length'])) {
            $ret['x-max-length'] = $arguments['max-length'];
        }

        if (isset($arguments['max-bytes'])) {
            $ret['x-max-length-bytes'] = $arguments['max-bytes'];
        }

        return $ret;
    }

    /**
     * Returns a name based on a definition
     *
     * @param array $definition The definition for grabbing the name
     *
     * @return string
     *
     * @throws Exception If components required in order to get the name are undefined
     */
    protected function getName(array $definition)
    {
        switch($definition['type']) {
            case "constant":
                $name = $definition['name'];
                break;
            case "static":
                if (!isset($definition['class'])) {
                    throw new Exception('Cannot find class definition!', 400);
                }
                if (!class_exists($definition['class'], true)) {
                    throw new Exception('Cannot load class!', 400);
                }
                $name = call_user_func(array($definition['class'], $definition['name']));
                if (!is_string($name) || empty($name)) {
                    throw new Exception('Invalid name for queue. The name needs to be a non-empty string!', 500);
                }
                break;
            case 'dynamic':
                if (!isset($definition['class'])) {
                    throw new Exception('Cannot find class definition!', 400);
                }
                if (!class_exists($definition['class'], true)) {
                    throw new Exception('Cannot load class!', 400);
                }
                $classInstance = new $definition['class'];
                $name = $classInstance->{$definition['name']}();
                if (!is_string($name) || empty($name)) {
                    throw new Exception('Invalid name for queue. The name needs to be a non-empty string!', 500);
                }
                break;
            case 'function':
                if (!function_exists($definition['name'])) {
                    throw new Exception('Cannot find function definition!', 400);
                }
                $name = call_user_func($definition['name']);
                break;
            default:
                throw new Exception('Invalid type for name. Type must be one of: constant, static, dynamic or function. Received ' . $definition['type'], 400);
        }
        return $name;
    }

    /**
     * Builds a bitmask of elements present in array and representing a constant
     *
     * @param array $constants The constants building the bitmask
     *
     * @return int
     */
    protected function buildBitmask(array $constants)
    {
        foreach ($constants as $constant) {
            if (!defined($constant)) {
                continue;
            }
            if (!isset($ret)) {
                $ret = constant($constant);
            } else {
                $ret |= constant($constant);
            }
        }

        if (!isset($ret)) {
            return AMQP_NOPARAM;
        } else {
            return $ret;
        }
    }

    /**
     * Initializes the dependencies
     *
     * @param array $dependencies The array of queues/exchanges dependencies
     */
    protected function initDependencies(array $dependencies)
    {
        if (isset($dependencies['queue'])) {
            foreach ($dependencies['queue'] as $queue) {
                $this->queue($queue);
            }
        }

        if (isset($dependencies['exchange'])) {
            foreach ($dependencies['exchange'] as $exchange) {
                $this->exchange($exchange);
            }
        }
    }
}