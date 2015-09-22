<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$container   = new \Symfony\Component\DependencyInjection\ContainerBuilder();
$fileLocator = new \Symfony\Component\Config\FileLocator(__DIR__ . '/config');
$loader      = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, $fileLocator);

$loader->load('services.yml');
$container->setParameter('config_path', __DIR__ . '/config');

while (true) {
    $builder = $container->get('amqp.builder.factory');
    $connection = $builder->connection('live');
    $connection->disconnect();
    unset($connection);
}
