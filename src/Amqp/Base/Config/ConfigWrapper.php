<?php
namespace Amqp\Base\Config;

use Amqp\Base\Config\Interfaces\Config;

class ConfigWrapper implements Config
{
    protected $configuration = array();

    public function __construct(array $config)
    {
        $this->configuration = $config;
    }

    public function getConfig()
    {
        return $this->configuration;
    }
}