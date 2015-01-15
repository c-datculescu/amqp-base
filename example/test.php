<?php
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\Config\FileLocator;

require_once "../vendor/autoload.php";

$file = array(__DIR__ . '/../config');

$container = new ContainerBuilder();
$loader = new YamlFileLoader(
    $container,
    new FileLocator($file)
);
$loader->load('services.yml');

/** @var Builder\Amqp $res */
$res = $container->get('amqp_builder');
$res->exchange('shop.club.dev');
$res->queue('test');

// check a recursive dependency
$res->queue('lol');
