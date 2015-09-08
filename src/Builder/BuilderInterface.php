<?php

namespace Amqp\Builder;

use Amqp\Consumer\ConsumerInterface;
use Amqp\Publisher\PublisherInterface;

interface BuilderInterface
{
    /**
     * @return ConsumerInterface
     */
    public function getConsumer();

    /**
     * @return PublisherInterface
     */
    public function getPublisher();

    /**
     * @param array $configuration
     * @return BuilderInterface
     */
    public function setConfiguration(array $configuration);
}