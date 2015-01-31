<?php
namespace Amqp\Util\Builder;

use Amqp\Base\Config\Processor;
use Amqp\Util\Config\Consumer;
use Amqp\Util\Listener\Interfaces\Listener as ListenerInterface;

class Listener
{
    protected $listenerConfiguration = array();

    public function __construct(Processor $processor)
    {
        $this->listenerConfiguration = $processor->getDefinition(new Consumer());
    }

    public function listener(ListenerInterface $listenerType, $name)
    {
        if (!isset($this->listenerConfiguration['consumer'][$name])) {
            throw new \UnexpectedValueException("Cannot locate listener definition for " . $name . "!");
        }

        $listenerType->setConfiguration($this->listenerConfiguration['consumer'][$name]);

        return $listenerType;
    }
}