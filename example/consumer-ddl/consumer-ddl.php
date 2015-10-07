<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$loader             = new \Amqp\Base\Config\YamlConfigLoader(__DIR__ . '/config/config.yml');
$processor          = new \Amqp\Base\Config\Processor($loader);
$builder            = new \Amqp\Base\Builder\Amqp($processor);

$config = [
    'bulkAck'                   => false,
    'reprocess_counter'         => 5,
    'reject_target_exchange'    => 'reject',
    'reject_target_routingKey'  => 'test',
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
        echo date("Y-m-d H:i:s") . " Received message!\n";
        return false;
    }
}

$queue = $builder->queue('consumer');
$rejectExchange = $builder->exchange($config['reject_target_exchange']);

$exchange = $builder->exchange('publisher');
$publisher = new \Amqp\Util\Publisher\Simple();
$publisher->setExchange($exchange);

$processor = new Processor();
$processor->setPublisher($publisher);

$simpleListener = new \Amqp\Util\Listener\Simple();
$simpleListener->setQueue($queue);
$simpleListener->setConfiguration($config);
$simpleListener->setProcessor($processor);
$simpleListener->setRejectTarget($rejectExchange, $config['reject_target_routingKey']);

echo date("Y-m-d H:i:s") . " Started listener for test!\n";
$simpleListener->listen();