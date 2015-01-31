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


    public function __construct(Amqp $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function publish($message, $routingKey = '', array $properties = array())
    {
        $exchange = $this->builder->exchange($this->configuration['exchange']);

        $this->exchange = $exchange;

        if (!isset($properties['timestamp'])) {
            $properties['timestamp'] = microtime(true);
        }

        $response = $this->exchange->publish($message, $routingKey, AMQP_NOPARAM, $properties);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }
}