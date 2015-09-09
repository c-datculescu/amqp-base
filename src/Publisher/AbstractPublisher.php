<?php

namespace Amqp\Publisher;

use Amqp\Adapter\AdapterInterface;

abstract class AbstractPublisher implements PublisherInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface)
    {
        $this->adapter = $adapterInterface;
    }
}