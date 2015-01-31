<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class ProcessorRpc implements \Amqp\Util\Interfaces\TimeoutProcessor
{
    public function process(\AMQPEnvelope $message)
    {
        print_r($message);
    }

    public function timeout()
    {
        echo "Timeout\n";
    }
}

$container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/config'));
$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');

$publisher = $container->get('publisher.demo');
$publisher->publish("test", "routing-key");