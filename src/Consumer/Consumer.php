<?php

namespace Amqp\Consumer;

use Amqp\Adapter\AdapterInterface;

class Consumer extends AbstractConsumer
{

    /**
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface)
    {
        // TODO: Implement __construct() method.
    }

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