<?php
namespace Amqp\Adapter;

interface AdapterInterface
{
    public function publish($exchangeName, $message, $routingKey);

    public function listen($queue, callable $callable);

    public function getMessage($queue);
}