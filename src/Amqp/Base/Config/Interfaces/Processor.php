<?php
namespace Amqp\Base\Config\Interfaces;

use Symfony\Component\Config\Definition\ConfigurationInterface;

interface Processor
{
    /**
     * Retrieves the configuration for the main amqp defintions
     *
     * @param string                 $file         The file that we should have the configuration in
     * @param ConfigurationInterface $configurator The configurator interface which wil validate the config
     * @param string                 $type         The type of configuration we are we need to read
     *
     * @return array
     */
    public function getDefinition($file, ConfigurationInterface $configurator, $type = '');
}