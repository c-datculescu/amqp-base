<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/config'));
$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');

$publisher = $container->get('publisher.demo');
$publisher->publish("test", "routing-key");


