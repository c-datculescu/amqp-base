<?php

namespace Amqp\Publisher;

use Amqp\Adapter\AdapterInterface;
use Amqp\Message\MessageInterface;

interface PublisherInterface
{
    /**
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface);

    /**
     * @param string $exchange
     * @param MessageInterface $message
     * @param string $routingKey
     * @return boolean
     */
    public function publish($exchange, MessageInterface $message, $routingKey);
}