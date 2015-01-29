<?php
namespace Amqp\Base\Config\Interfaces;

interface Loader
{
    /**
     * Load configuration
     *
     * @param string $filename Config filename
     *
     * @return array
     */
    public function load($filename);
}