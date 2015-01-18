<?php
namespace Amqp\Util\Builder;

use Amqp\Base\Builder\Amqp;

class Publisher
{
    protected $configuration = array();

    protected $publishers = array();

    public function __construct(array $configuration, Amqp $builder)
    {
        $this->configuration = $configuration;
        $this->amqpBuilder = $builder;
    }

    public function publisher($name)
    {
        if (isset($this->publishers[$name])) {
            return $this->publishers[$name];
        }

        if (!isset($this->configuration['publisher'][$name])) {
            throw new \Exception("Cannot locate the definition for publisher " . $name);
        }

        // initialize the listener
        $instanceName = $this->configuration['publisher'][$name]['class'];

        /** @var \Amqp\Util\Publisher\Interfaces\Publisher|\Amqp\Util\Publisher\Interfaces\Rpc $instance */
        $instance = new $instanceName($this->configuration['publisher'][$name], $this->amqpBuilder);

        $this->publishers[$name] = $instance;

        return $instance;
    }
}