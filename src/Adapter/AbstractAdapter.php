<?php

namespace Amqp\Adapter;

abstract class AbstractAdapter extends AbstractAdapterAware implements AdapterInterface
{
    /**
     * Configuration
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config The adapter config
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