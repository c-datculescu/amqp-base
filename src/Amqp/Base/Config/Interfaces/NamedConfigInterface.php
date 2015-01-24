<?php
namespace Amqp\Base\Config\Interfaces;

interface NamedConfigInterface
{
    /**
     * Returns the type of configuration for which this configurator applies
     *
     * @return string
     */
    public function getType();
}