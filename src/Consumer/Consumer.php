<?php

namespace Amqp\Consumer;

class Consumer extends AbstractConsumer
{

    /**
     * @param string $queue
     * @param callable $callback
     * @return void
     */
    public function listen($queue, callable $callback)
    {
        // TODO: Implement listen() method.
    }

    /**
     * @param string $queue
     * @return \Amqp\Message\MessageInterface
     */
    public function getMessage($queue)
    {
        // TODO: Implement getMessage() method.
    }
}