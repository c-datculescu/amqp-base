<?php

use Amqp\Adapter\ExtAdapter;
use Amqp\Publisher;

require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

$publisher = new Publisher();
$publisher->setAdapter(new ExtAdapter($config));

$routing_keys = ['foo', 'bar', 'foo.bar', 'bar.foo', null];

for ($i = 0; $i < 10; $i++) {
    $msg = new Amqp\Message();
    $msg->setPayload('Message ' . $i);
    $publisher->publish('global', $msg, $routing_keys[rand(0, count($routing_keys) - 1)]);
}