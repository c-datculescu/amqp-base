<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class Processor implements \Amqp\Util\Interfaces\TimeoutProcessor
{
    public function process(\AMQPEnvelope $message)
    {
        print_r($message->getBody());
        return true;
    }

    public function timeout()
    {
        echo "Timing out!!!\n";
    }
}

$configDir = array(__DIR__ . '/../config/');

// initialize the base-non-di components:
// the file locator to be used when searching for configuration
$fileLocator = new Symfony\Component\Config\FileLocator($configDir);

// the processor for validating the definitions imported from configuration
$definitionProcessor = new Symfony\Component\Config\Definition\Processor;

// create the config loader
$configLoader = new \Amqp\Base\Config\YamlConfigLoader($fileLocator);

// initialize the configuration factory
$configFactory = new \Amqp\Base\Config\Processor($configLoader, $fileLocator, $definitionProcessor);

// retrieve the configurations for publishers/consumers/amqp
$configAmqp = $configFactory->getDefinition('rpc', new \Amqp\Base\Config\Amqp());

// set up the base-non-di builder
$builder = new Amqp\Base\Builder\Amqp($configAmqp);

$configPublishers = $configFactory->getDefinition('rpc', new \Amqp\Util\Config\Publisher());

$publisherBuilder = new \Amqp\Util\Builder\Publisher($configPublishers, $builder);
$publisher = $publisherBuilder->publisher('rpc_test');

$publisher->attach(new Processor());

$publisher->publish('test message', 'test.rpc');



