<?php

namespace Amqp\Consumer;

use Amqp\Adapter\AdapterInterface;

interface ConsumerInterface
{
    /**
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface);

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