<?php

use Amqp\Adapter\ExtAdapter;
use Amqp\Consumer;
use Amqp\Message\MessageInterface;
use Amqp\Publisher;

require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

$adapter = new ExtAdapter($config);

$publisher = new Publisher();
$publisher->setAdapter($adapter);
$routing_keys = ['foo', 'bar', 'foo.bar', 'bar.foo', null];

echo "[ ] Publishing...", PHP_EOL;
for ($i = 0; $i < 10; $i++) {
    $msg = new Amqp\Message();
    $msg->setPayload('Message ' . $i);
    $publisher->publish('global', $msg, $routing_keys[rand(0, count($routing_keys) - 1)]);
    echo '[+] ', $msg->getPayload(), PHP_EOL;
}
echo "[!] Published!", PHP_EOL, str_repeat('-', 16), PHP_EOL;

$consumer = new Consumer();
$consumer->setAdapter($adapter);

$i = 0;
echo "[ ] Listening...", PHP_EOL;
$consumer->listen('debug', function (MessageInterface $msg, Amqp\Message\Result $result) use (&$i) {
    echo "[+] ", $msg->getPayload(), PHP_EOL;
    $result->ack();

    if (++$i == 10) {
        return $result->stop();
    }
});

echo '[!] Finished!', PHP_EOL;