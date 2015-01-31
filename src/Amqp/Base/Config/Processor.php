<?php
namespace Amqp\Base\Config;

use Amqp\Base\Config\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\Processor as DefinitionProcessor;

class Processor implements Interfaces\Processor
{
    protected $config;

    /**
     * @var DefinitionProcessor
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

    public function __construct(Interfaces\Loader $loader) {
        $this->config = $loader->load();
        $this->processor = new DefinitionProcessor();
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

        $this->definitions[$type] = $processedConfiguration;

        return $this->definitions[$type];
    }
}