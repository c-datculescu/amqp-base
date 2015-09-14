<?php

namespace Amqp\Adapter;

use Amqp\Adapter\ExtAdapter\Helper;
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
     * @var array
     */
    protected $connections = [];

    /**
     * Queues instances
     * @var \AMQPQueue[]
     */
    protected $queues = [];

    /**
     * @inheritdoc
     * @todo Add more message properties
     */
    public function publish($exchangeName, MessageInterface $message, $routingKey = null)
    {
        try {
            $props = $message->getProperties();
            $props['delivery_mode'] = $message->getDeliveryMode();

            return $this->getExchange($exchangeName)->publish($message->getPayload(), $routingKey, AMQP_NOPARAM, $props);
        } catch (\Exception $e) {
            throw Helper\Exception::convert($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function listen($queue, callable $callback, array $options = [])
    {
        $options = array_merge($this->defaultConfig['listener'], $options);
        $connectionConfig = $this->getConfig('connection', $this->config['queues'][$queue]['connection']);
        $ackAt = isset($options['multi_ack']) ? ceil($connectionConfig['prefetch_count'] / 2) : 0;

        try {
            $queue = $this->getQueue($queue);
            $acknowledged = 0;
            $queue->consume(\Closure::bind(function (\AMQPEnvelope $envelope) use ($callback, $queue, $ackAt, &$acknowledged) {
                $result = new Message\Result();
                call_user_func($callback, Helper\Message::convert($envelope), $result);

                if ($result->getStatus()) {
                    if ($ackAt > 1) {
                        if (0 == ++$acknowledged % $ackAt) {
                            $queue->ack($envelope->getDeliveryTag(), AMQP_MULTIPLE);
                        }
                    } else {
                        $queue->ack($envelope->getDeliveryTag());
                    }
                } else {
                    $queue->nack($envelope->getDeliveryTag(), $result->isRequeue() ? AMQP_REQUEUE : AMQP_NOPARAM);
                }

                if ($result->isStop()) {
                    return false;
                }
            }, $this), Helper\Options::toFlags($options));
        } catch (\Exception $e) {
            throw Helper\Exception::convert($e);
        }
    }

    /**
     * Get exchange
     *
     * @param string $name The exchange name
     *
     * @return \AMQPExchange
     */
    protected function getExchange($name)
    {
        if (isset($this->exchanges[$name])) {
            return $this->exchanges[$name];
        }

        $config = $this->getConfig('exchange', $name);
        if (null === $config) {
            throw new \InvalidArgumentException("Exchange definition '{$name}' doesn't exists.");
        }

        $connection = $this->getConnection($config['connection']);
        $this->exchanges[$name] = $exchange = new \AMQPExchange($connection['channel']);

        $exchange->setName(is_callable($config['name']) ? call_user_func($config['name']) : $config['name']);
        $exchange->setType(isset($config['type']) ? $config['type'] : 'topic');
        $exchange->setFlags(Helper\Options::toFlags($config));
        $exchange->declareExchange();

        if (isset($config['bindings'])) {
            foreach ($config['bindings'] as $binding) {
                try {
                    $this->getExchange($binding['exchange']);
                } catch (\InvalidArgumentException $e) {
                }

                $exchange->bind($binding['exchange'], $binding['routing_key'],
                    isset($binding['arguments']) ? $binding['arguments'] : []);
            }
        }

        if (isset($config['attributes'])) {
            $exchange->setArguments($config['attributes']);
        }

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

        $config = $this->getConfig('connection', $name);
        if (null == $config) {
            throw new \InvalidArgumentException("Connection '{$name}' doesn't exists.");
        }

        $connection = new \AMQPConnection($config);
        $connection->connect();

        $channel = new \AMQPChannel($connection);
        if (isset($config['prefetch_count'])) {
            $channel->setPrefetchCount($config['prefetch_count']);
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

        $config = $this->getConfig('queue', $name);
        if (null === $config) {
            throw new \InvalidArgumentException("Queue definition '{$name}' doesn't exists.");
        }

        $connection = $this->getConnection($config['connection']);
        $this->queues[$name] = $queue = new \AMQPQueue($connection['channel']);

        $queue->setFlags(Helper\Options::toFlags($config));
        $queue->setName(is_callable($config['name']) ? call_user_func($config['name']) : $config['name']);
        $queue->declareQueue();

        if (isset($config['bindings'])) {
            foreach ($config['bindings'] as $binding) {
                try {
                    $this->getExchange($binding['exchange']);
                } catch (\InvalidArgumentException $e) {}

                $queue->bind($binding['exchange'], $binding['routing_key'],
                    isset($binding['arguments']) ? $binding['arguments'] : []);
            }
        }

        if (isset($config['attributes'])) {
            if (isset($config['attributes']['x-dead-letter-exchange'])) {
                try {
                    $this->getExchange($config['attributes']['x-dead-letter-exchange']);
                } catch (\InvalidArgumentException $e) {}
            }

            $queue->setArguments($config['attributes']);
        }

        return $queue;
    }

    /**
     * Disconnect and clear local objects cache
     */
    public function __destruct()
    {
        foreach ($this->connections as &$connection) {
            $connection['connection']->disconnect();
            unset($connection['connection'], $connection['channel']);
        }
        $this->connections = [];
    }
}