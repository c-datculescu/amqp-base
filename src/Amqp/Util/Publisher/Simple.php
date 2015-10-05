<?php
namespace Amqp\Util\Publisher;

use Amqp\Base\Builder\Interfaces\Amqp;
use Amqp\Util\Publisher\Interfaces\Publisher;

class Simple implements Publisher
{
    protected $builder;

    protected $configuration;

    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    /**
     * {@inheritdoc}
     */
    public function setExchange(\AMQPExchange $exchange)
    {
        $this->exchange = $exchange;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function publish($message, $routingKey = '', array $properties = array())
    {
        if (!isset($properties['timestamp'])) {
            $properties['timestamp'] = microtime(true);
        }

        // make messages durable by default
        if (!isset($properties['delivery_mode'])) {
            $properties['delivery_mode'] = 2;
        }
        try {
            $response = $this->exchange->publish($message, $routingKey, AMQP_NOPARAM, $properties);
        } catch (\AMQPException $e) {
            $this->reconnect();
            $response = $this->exchange->publish($message, $routingKey, AMQP_NOPARAM, $properties);
        }

        return $response;
    }

    /**
     * Attempts to reconnect on a dead connection
     * Usable for long running processes, where the stale connections get collected
     * after some time
     *
     * @return void
     */
    protected function reconnect()
    {
        $connection = $this->exchange->getConnection();
        $channel = $this->exchange->getChannel();

        $connection->reconnect();

        // since the channel is also dead, need to somehow revive it. This can be
        // done only by calling the constructor of the channel
        $channel->__construct($connection);
    }
}
