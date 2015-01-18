<?php
namespace Amqp\Base\Interfaces;

interface NamedConfigInterface
{
    /**
     * Returns the type of configuration for which this configurator applies
     *
     * @return string
     */
    public function getType();
}