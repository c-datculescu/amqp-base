<?php

namespace Amqp\Publisher;

use Amqp\Message\MessageInterface;

interface PublisherInterface
{
    /**
     * @param string $exchange
     * @param MessageInterface $message
     * @param string $routingKey
     * @return boolean
     */
    public function publish($exchange, MessageInterface $message, $routingKey);
}