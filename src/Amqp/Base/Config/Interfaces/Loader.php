<?php
namespace Amqp\Base\Config\Interfaces;

interface Loader
{
    /**
     * Load configuration
     *
     * @return array
     */
    public function load();
}