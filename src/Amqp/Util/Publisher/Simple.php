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

        $response = $this->exchange->publish($message, $routingKey, AMQP_NOPARAM, $properties);

        return $response;
    }
}