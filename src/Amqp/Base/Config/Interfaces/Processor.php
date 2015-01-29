<?php
namespace Amqp\Base\Config\Interfaces;

use Symfony\Component\Config\Definition\ConfigurationInterface;

interface Processor
{
    /**
     * Retrieves the configuration for the main amqp definitions
     *
     * @param NamedConfigInterface $configurator The configurator interface which wil validate the config
     *
     * @return array
     */
    public function getDefinition(NamedConfigInterface $configurator);
}