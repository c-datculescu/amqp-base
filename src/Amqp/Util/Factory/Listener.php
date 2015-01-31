<?php
namespace Amqp\Util\Factory;

use Amqp\Base\Config\Processor;
use Amqp\Util\Listener\Interfaces\Listener as ListenerInterface;

class Listener
{
    /**
     * The entire listener configuration
     * @var array
     */
    protected $listenerConfiguration = array();

    /**
     * @param Processor $processor The processor
     */
    public function __construct(Processor $processor)
    {
        $this->listenerConfiguration = $processor->getDefinition(new \Amqp\Util\Config\Listener());
    }

    /**
     * Populates the listener with the configuration and returns the listener ready to be used
     *
     * @param ListenerInterface $listenerType The listener that needs to be configured
     * @param string            $name         The name of the listener from the configuration file
     *
     * @return ListenerInterface
     */
    public function listener(ListenerInterface $listenerType, $name)
    {
        if (!isset($this->listenerConfiguration['listener'][$name])) {
            throw new \UnexpectedValueException("Cannot locate listener definition for " . $name . "!");
        }

        $listenerType->setConfiguration($this->listenerConfiguration['listener'][$name]);

        return $listenerType;
    }
}