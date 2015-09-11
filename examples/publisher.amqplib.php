<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

var_dump($config);

$adapter = new \Amqp\Adapter\AmqplibAdapter($config);

$msg = new \Amqp\Message\Message();
$msg->setPayload('asjghjafhglkag');
$msg->setDeliveryMode(2);
$msg->setHeaders(['x-foo' =>'sfgsd']);

$adapter->publish('global', $msg);
$adapter->listen(
    'debug',
    function ($msg) {
        var_dump($msg);
    }
);