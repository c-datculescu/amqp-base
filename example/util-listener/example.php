<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$loader = new \Amqp\Base\Config\YamlConfigLoader(__DIR__ . '/config/config.yml');

// initialize the configuration factory
$configFactory = new \Amqp\Base\Config\Processor($loader);

// set up the base-non-di builder
$builder = new Amqp\Base\Builder\Amqp($configFactory);

$listener = new \Amqp\Util\Listener\Simple(array(
    'queue'          => 'test',
    'onProcessError' => 'requeue',
    'bulkAck'        => '50'
), $builder);

class Processor implements \Amqp\Util\Interfaces\Processor
{
    public function process(\AMQPEnvelope $message)
    {
        echo 'FOO:' . $message->getBody() . "\n";
    }
}

class Processor2 extends Processor
{
    public function process(\AMQPEnvelope $message)
    {
        parent::process($message);
        echo 'BAR:' . $message->getBody() . "\n";
    }
}

$listener->setProcessor(new Processor2());
$listener->listen();