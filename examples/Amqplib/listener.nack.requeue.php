<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

$msg = new \Amqp\Message();
$msg->setDeliveryMode(2);
$msg->setHeaders(['x-foo' =>'sfgsd']);
$msg->setPayload(uniqid());

$adapter = new \Amqp\Adapter\AmqplibAdapter($config);
$adapter->publish('global', $msg);

$adapter->listen('debug', function (\Amqp\Message\MessageInterface $message, \Amqp\Message\Result $result) {
    echo $message->getPayload(),PHP_EOL;

    // not cool, but you can requeue
    return $result->requeue()->nack();
});