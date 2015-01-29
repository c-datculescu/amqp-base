<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$loader = new \Amqp\Base\Config\YamlConfigLoader();
$config = $loader->load(__DIR__ . '/../util-listener/config/config.yml');

// initialize the configuration factory
$configFactory = new \Amqp\Base\Config\Processor($config);

// set up the base-non-di builder
$builder = new Amqp\Base\Builder\Amqp($configFactory);

$publisher = new Amqp\Util\Publisher\Simple(array(
    'exchange' =>  'test',
    'timeout' => '100000000'
), $builder);

for ($i = 0; $i < 10; $i++) {
    $publisher->publish('test message ' . time(), '');
    sleep(1);
    echo $i . PHP_EOL;
}


