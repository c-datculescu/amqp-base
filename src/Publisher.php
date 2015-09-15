<?php

namespace Amqp;

use Amqp\Message\MessageInterface;
use Amqp\Publisher\AbstractPublisher;

class Publisher extends AbstractPublisher
{
    /**
     * @param string $exchange
     * @param MessageInterface $message
     * @param string $routingKey
     * @return boolean
     */
    public function publish($exchange, MessageInterface $message, $routingKey = '')
    {
        return $this->adapter->publish($exchange, $message, $routingKey);
    }
}