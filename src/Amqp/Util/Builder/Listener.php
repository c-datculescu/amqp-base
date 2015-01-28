<?php
namespace Amqp\Util\Builder;

use Amqp\Base\Builder\Interfaces\Amqp;
use Amqp\Base\Config\Interfaces\Config;

class Listener implements Interfaces\Listener
{
    /**
     * Current local configuration
     *
     * @var Config
     */
    protected $configuration = array();

    /**
     * @var Amqp
     */
    protected $amqpBuilder;

    /**
     * @var \Amqp\Util\Listener\Interfaces\Listener[]
     */
    protected $listeners = array();

    /**
     * @param Config $configuration The configuration
     * @param Amqp   $builder       The Amqp Builder
     */
    public function __construct(Config $configuration, Amqp $builder)
    {
        $this->configuration = $configuration->getConfig();
        $this->amqpBuilder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function listener($name)
    {
        if (isset($this->listeners[$name])) {
            return $this->listeners[$name];
        }

        if (!isset($this->configuration['consumer'][$name])) {
            throw new Exception("Cannot locate the definition for listener " . $name);
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