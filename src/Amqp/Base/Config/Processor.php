<?php
namespace Amqp\Base\Config;

use Amqp\Base\Config\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor as s2processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;

class Processor implements Interfaces\Processor
{
    protected $config;

    /**
     * @var s2processor
     */
    protected $processor;

    /**
     * Caches the definitions in case the methods are called multiple times
     * @var array
     */
    protected $definitions = array(
        'amqp'      => array(),
        'consumers' => array(),
        'publishers' => array(),
    );

    public function __construct(array $config) {
        $this->config = $config;
        $this->processor = new \Symfony\Component\Config\Definition\Processor();
    }

    public function getDefinition(NamedConfigInterface $configurator)
    {
        $type = $configurator->getType();

        // returns the cached version of the configuration
        if (!empty($this->definitions[$type])) {
            return $this->definitions[$type];
        }
        // process and validate the configuration
        $processedConfiguration = $this->processor->processConfiguration($configurator, $this->config);

        $configObject = new ConfigWrapper($processedConfiguration);

        $this->definitions[$type] = $configObject;

        return $configObject;
    }
}