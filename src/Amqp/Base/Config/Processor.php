<?php
namespace Amqp\Base\Config;

use Amqp\Base\Config\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor as s2processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;

class Processor implements Interfaces\Processor
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
        'consumers' => array(),
        'publishers' => array(),
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


    public function getDefinition($file, ConfigurationInterface $configurator, $type = '')
    {
        if ($type == '') {
            if ($configurator instanceof NamedConfigInterface) {
                $type = $configurator->getType();
            }
        }

        // returns the cached version of the configuration
        if (!empty($this->definitions[$type])) {
            return $this->definitions[$type];
        }

        // find the yml file
        $file = $this->locator->locate($file . ".yml");

        // locate the needed file
        $configurationValues = $this->loader->load($file);

        // process and validate the configuration
        $processedConfiguration = $this->processor->processConfiguration($configurator, $configurationValues);

        $this->definitions[$type] = $processedConfiguration;

        return $processedConfiguration;
    }
}