<?php
/**
 * Simple example using s2 DI component for injecting the services
 * Also showcases how to retrieve a queue/exchange to be used later
 */
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\Config\FileLocator;

require_once __DIR__ . "/../../vendor/autoload.php";
$file = array(__DIR__ . "/config/");

$container = new ContainerBuilder();
$locator = new FileLocator($file);
$loader = new YamlFileLoader(
    $container,
    $locator
);
$loader->load('services.yml');
$container->set('fileLocator', $locator);


/** @var \Amqp\Base\Builder\Amqp $res */
$res = $container->get('amqpBuilder');

// retrieve an exchange
$res->exchange('shop.club.dev');

// retrieve a queue
$res->queue('test');