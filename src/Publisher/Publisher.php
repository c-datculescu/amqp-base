<?php

namespace Amqp\Publisher;

use Amqp\Message\MessageInterface;

class Publisher extends AbstractPublisher
{
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