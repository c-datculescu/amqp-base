<?php
/**
 * Running this example twice should crash the first time and keep listening the second time
 */
require_once __DIR__ . '/../../vendor/autoload.php';

$loader             = new \Amqp\Base\Config\YamlConfigLoader(__DIR__ . '/config/config.yml');
$processor          = new \Amqp\Base\Config\Processor($loader);
$builder            = new \Amqp\Base\Builder\Amqp($processor);

$config = [
    'bulkAck'                   => false,
    'skip_if_redelivered'       => true,
];

class Processor implements \Amqp\Util\Interfaces\Processor
{
    /**
     * @var \Amqp\Util\Publisher\Interfaces\Publisher
     */
    protected $publisher;

    public function setPublisher(\Amqp\Util\Publisher\Interfaces\Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function process(\AMQPEnvelope $msg)
    {
        throw new \Exception("First exception!!!");
    }
}

// retrieve the exchange. this will trigger all other exchanges to get declared
$exchange = $builder->exchange('main_exchange');

// retrieve the queue. This will trigger all other queues to be declared
$queue = $builder->queue('main_queue');

$publisher = new \Amqp\Util\Publisher\Simple();
$publisher->setExchange($exchange);

$processor = new Processor();
$processor->setPublisher($publisher);

$simpleListener = new \Amqp\Util\Listener\Simple();
$simpleListener->setQueue($queue);
$simpleListener->setConfiguration($config);
$simpleListener->setProcessor($processor);

echo date("Y-m-d H:i:s") . " Started listener for test!\n";
$simpleListener->listen();
