<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class testProcessor implements \Amqp\Util\Interfaces\Processor
{
    public function process(\AMQPEnvelope $message)
    {
        echo 'Test message is: ' . $message->getBody() . PHP_EOL;
    }
}

class dateTimeProcessor extends testProcessor
{
    public function process(\AMQPEnvelope $message)
    {
        echo 'Date time is: ' . $message->getBody() . PHP_EOL;
    }
}

$container   = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$fileLocator = new \Symfony\Component\Config\FileLocator(__DIR__ . '/config');
$loader      = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, $fileLocator);

$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');

$listener = $container->get('listener.test');
//$listener = $container->get('listener.dateTime');

$listener->listen();
