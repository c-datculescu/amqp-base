<?php
namespace Config;

use Symfony\Component\Config\Definition\Processor as s2processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;

class Processor
{
    /**
     * The directories where we can find out the configuration files
     * @var array
     */
    protected $configDir = array();

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var s2processor
     */
    protected $processor;

    /**
     * @var FileLocator
     */
    protected $locator;

    /**
     * Caches the definitions in case the methods are called multiple times
     * @var array
     */
    protected $definitions = array(
        'amqp'      => array(),
        'listeners' => array(),
        'consumers' => array(),
    );

    /**
     * @param LoaderInterface $loader
     * @param FileLocator $locator
     * @param s2processor $processor
     */
    public function __construct(
        LoaderInterface $loader,
        FileLocator $locator,
        s2processor $processor

    ) {
        $this->loader = $loader;
        $this->locator = $locator;
        $this->processor = $processor;
    }

    /**
     * Retrieves the configuration for the main amqp defintions
     *
     * @param string      $file         The file that we should have the configuration in
     * @param AmqpConfig  $configurator The configuration type (allowed types are amqp, listener, publisher)
     *
     * @return array
     */
    public function getAmqpDefinition(
        $file,
        AmqpConfig $configurator
    ) {
        // returns the cached version of the configuration
        if (!empty($this->definitions['amqp'])) {
            return $this->definitions['amqp'];
        }

        // find the yml file
        $file = $this->locator->locate($file . ".yml");

        // locate the needed file
        $configurationValues = $this->loader->load($file);

        // process and validate the configuration
        $processedConfiguration = $this->processor->processConfiguration($configurator, $configurationValues);

        $this->definitions['amqp'] = $processedConfiguration;

        return $processedConfiguration;
    }
}