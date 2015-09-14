<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

$adapter = new \Amqp\Adapter\AmqplibAdapter($config);

$msg = new \Amqp\Message\Message();
$msg->setDeliveryMode(2);
$msg->setHeaders(['x-foo' =>'sfgsd']);
$msg->setPayload(uniqid());

while(true) {
    $adapter->publish('global', $msg);
}