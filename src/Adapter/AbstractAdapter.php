<?php

namespace Amqp\Adapter;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Configuration
     * @var array
     */
    protected $config;

    /**
     * Set config
     *
     * @param array $config Configuration options
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Get the config
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }
}