<?php

namespace Amqp\Publisher;

use Amqp\Builder;

class Lazy implements PublisherInterface
{
    /**
     * Amqp factory
     * @var Builder
     */
    protected $builder;

    /**
     * Exchange internal name
     * @var string
     */
    protected $exchangeName;

    protected $options = [
        'max_tries' => 5,
        'sleep'     => 20, // Milliseconds
    ];

    /**
     * Constructor
     *
     * @param $builder      Builder Amqp factory object
     * @param $exchangeName string  Exchange internal (configuration key) name
     * @param $options      array   Options
     */
    public function __construct(Builder $builder, $exchangeName, $options = [])
    {
        $this->builder = $builder;
        $this->exchangeName = $exchangeName;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @inheritdoc
     */
    public function publish($message, $routingKey = '', array $properties = [])
    {
        if (!isset($properties['timestamp'])) {
            $properties['timestamp'] = microtime(true);
        }

        // Make messages durable by default
        if (!isset($properties['delivery_mode'])) {
            $properties['delivery_mode'] = 2;
        }

        $sent = false;
        $try = 0;
        $response = null;
        $sleep = $this->options['sleep'];
        $lastException = null;

        do {
            try {
                $response = $this->getExchange()->publish($message, $routingKey, AMQP_NOPARAM, $properties);
                $sent = true;
            } catch (\AMQPConnectionException $e) {
                $lastException = $e;
                $this->builder->releaseConnAndDeps($this->exchangeName);
                usleep($sleep * 1000);
                $sleep += $sleep;
            }
        } while (!$sent && ++$try < $this->options['max_tries']);

        if (!$sent) {
            throw new Exception("Couldn't publish message.", $lastException->getCode(), $lastException);
        }

        return $response;
    }

    /**
     * Get exchange
     *
     * @return \AMQPExchange
     * @throws \Amqp\Base\Builder\Exception
     */
    protected function getExchange()
    {
        return $this->builder->exchange($this->exchangeName);
    }

    /**
     * @inheritdoc
     */
    public function setConfiguration(array $configuration)
    {
        throw new \RuntimeException('Deprecated. Will be removed');
    }

    /**
     * @inheritdoc
     */
    public function setExchange(\AMQPExchange $exchange)
    {
        throw new \RuntimeException('Deprecated. Will be removed');
    }
}