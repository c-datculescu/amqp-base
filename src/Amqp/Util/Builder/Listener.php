<?php
namespace Amqp\Util\Builder;

use Amqp\Base\Builder\Amqp;

class Listener
{
    protected $configuration = array();

    /**
     * @var Amqp
     */
    protected $amqpBuilder;

    protected $listeners = array();

    public function __construct(array $configuration, Amqp $builder)
    {
        $this->configuration = $configuration;
        $this->amqpBuilder = $builder;
    }

    public function listener($name)
    {
        if (isset($this->listeners[$name])) {
            return $this->listeners[$name];
        }

        if (!isset($this->configuration['consumer'][$name])) {
            throw new \Exception("Cannot locate the definition for listener " . $name);
        }

        // initialize the listener
        $instanceName = $this->configuration['consumer'][$name]['class'];

        /** @var \Amqp\Util\Listener\Interfaces\Listener $instance */
        $instance = new $instanceName($this->configuration['consumer'][$name], $this->amqpBuilder);

        $this->listeners[$name] = $instance;

        // allocate the watchers
        if (isset($this->configuration['consumer'][$name]['watchers'])) {
            foreach ($this->configuration['consumer'][$name]['watchers'] as $watcher) {
                // initialize the watcher and allocate it to the listener
                $class = $watcher['class'];
                $watcherInstance = new $class;
                if (isset($watcher['arguments'])) {
                    foreach ($watcher['arguments'] as $functionName => $parameter) {
                        $func = "set" . ucfirst($functionName);
                        $watcherInstance->$func($parameter);
                    }
                }

                $instance->attachMonitor($watcherInstance);
            }
        }

        return $instance;
    }
}