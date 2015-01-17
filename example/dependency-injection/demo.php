<?php
/**
 * Simple example using s2 DI component for injecting the services
 */
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\Config\FileLocator;

require_once "../../vendor/autoload.php";

$file = array(__DIR__);

$container = new ContainerBuilder();
$loader = new YamlFileLoader(
    $container,
    new FileLocator($file)
);
$loader->load('services.yml');

/** @var Builder\Amqp $res */
$res = $container->get('amqpBuilder');
$res->exchange('shop.club.dev');

