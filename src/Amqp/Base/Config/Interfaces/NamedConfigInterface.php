<?php
namespace Amqp\Base\Config\Interfaces;

use Symfony\Component\Config\Definition\ConfigurationInterface;

interface NamedConfigInterface extends ConfigurationInterface
{
    /**
     * Returns the type of configuration for which this configurator applies
     *
     * @return string
     */
    public function getType();
}