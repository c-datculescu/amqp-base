<?php

namespace Amqp\Publisher;

use Amqp\Adapter\AdapterInterface;
use Amqp\Message\MessageInterface;

class Publisher extends AbstractPublisher
{

    /**
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface)
    {
        // TODO: Implement __construct() method.
    }

    /**
     * @param string $exchange
     * @param MessageInterface $message
     * @param string $routingKey
     * @return boolean
     */
    public function publish($exchange, MessageInterface $message, $routingKey)
    {
        // TODO: Implement publish() method.
    }
}