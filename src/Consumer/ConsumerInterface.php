<?php

namespace Amqp\Consumer;

interface ConsumerInterface
{
    /**
     * @param string $queue
     * @param callable $callback
     * @return void
     */
    public function listen($queue, callable $callback);

    /**
     * @param string $queue
     * @return \Amqp\Message\MessageInterface
     */
    public function getMessage($queue);
}