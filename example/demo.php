<?php
/**
 * Simple example using s2 DI component for injecting the services
 * Also showcases how to retrieve a queue/exchange to be used later
 */
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\Config\FileLocator;

require_once "../vendor/autoload.php";

$file = array(__DIR__);

$container = new ContainerBuilder();
$loader = new YamlFileLoader(
    $container,
    new FileLocator($file)
);
$loader->load('services.yml');

/** @var Builder\Amqp $res */
$res = $container->get('amqpBuilder');

// retrieve an exchange
$res->exchange('shop.club.dev');

// retrieve a queue
$res->queue('test');
