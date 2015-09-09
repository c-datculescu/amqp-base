<?php

namespace Amqp\Adapter;

abstract class AdapterAbstract implements AdapterInterface
{
    /**
     * Configuration
     * @var array
     */
    protected $config;

    /**
     * Constructor
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

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