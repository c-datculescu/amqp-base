<?php

namespace Amqp;

use Amqp\Consumer\AbstractConsumer;

class Consumer extends AbstractConsumer
{
    /**
     * @param string $queue
     * @param callable $callback
     * @param array $options
     * @return void
     */
    public function listen($queue, callable $callback, array $options = array())
    {
        return $this->adapter->listen($queue, $callback, $options);
    }
}