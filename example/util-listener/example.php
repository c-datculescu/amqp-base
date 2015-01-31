<?php

require_once __DIR__ . '/../../vendor/autoload.php';

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

$container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/config'));
$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');
$listener = $container->get('listener.demo');

$listener->listen();