<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

$adapter = new \Amqp\Adapter\AmqplibAdapter($config);

for ($i = 3; $i--;) {
    $msg = new \Amqp\Message\Message();
    $msg->setDeliveryMode(2);
    $msg->setHeaders(['x-foo' =>'sfgsd']);
    $msg->setPayload(uniqid());

    $adapter->publish('global', $msg);
}

$counter = 0;
$adapter->listen('debug', function (\Amqp\Message\Message $message, \Amqp\Message\Result $result) use (&$counter) {
    echo $message->getPayload(),PHP_EOL;

    $result->ack();

    // process only 2 messages
    if (++$counter === 2) {
        return $result->stop();
    }
});