<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$container   = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$fileLocator = new \Symfony\Component\Config\FileLocator(__DIR__ . '/config');
$loader      = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, $fileLocator);

$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');

//var_dump($container->get('amqp.builder.factory')->releaseConnAndDeps());

$publisher = $container->get('amqp.builder.factory')->publisher('testExchange');

$message = 'test';
$publisher->publish($message, "example.test");

$message = 'Current date/time is ' . date('c');
$publisher->publish($message, "example.dateTime");

