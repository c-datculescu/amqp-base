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
     * Default configurations for components
     *
     * @var array
     */
    protected $defaultConfig = [
        'connection' => [
            'host'               => 'localhost',
            'port'               => 5672,
            'vhost'              => '/',
            'login'              => 'guest',
            'password'           => 'guest',
            'connect_timeout'    => 1,
            'read_write_timeout' => 3,
            'heartbeat'          => 10,
            'keepalive'          => true,
            'prefetch_count'     => 3,
            'publisher_confirms' => false,
        ],
        'queue'      => [
            'arguments'   => [],
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
            'bindings'    => [],
            'type'        => 'topic',
        ],
        'exchange'   => [
            'type'      => 'topic',
            'arguments' => [],
            'passive'   => false,
            'durable'   => true,
        ],
        'listener'   => [
            'auto_ack'  => false,
            'multi_ack' => false,
        ],
    ];

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
     * @param null|string $type Type of config
     * @param null|string $key  Configuration key
     *
     * @return array
     */
    public function getConfig($type = null, $key = null)
    {
        if (null === $type || null === $key) {
            return $this->config;
        }

        if (!isset($this->config[$type . 's'])) {
            return null;
        }

        $config = $this->config[$type . 's'];

        if (!isset($config[$key])) {
            return false;
        }

        return array_merge($this->defaultConfig[$type], $config[$key]);
    }
}