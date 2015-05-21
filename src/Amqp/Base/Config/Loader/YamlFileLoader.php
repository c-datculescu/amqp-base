<?php

namespace Amqp\Base\Config\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends FileLoader
{
    /**
     * Loads a resource.
     *
     * @param mixed       $resource The resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @return array
     * @throws \Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        $config = $this->parseYaml($resource);
        if (isset($config['imports']) && is_array($config['imports'])) {
            foreach ($config['imports'] as $import) {
                $config = array_merge_recursive($config, (array) $this->import($import['resource'], null, true, $resource));
            }

            unset($config['imports']);
        }

        return $config;
    }

    /**
     * Parse yaml content|file
     *
     * @param string|mixed $resource
     *
     * @return array
     */
    protected function parseYaml($resource)
    {
        return Yaml::parse($this->loadResourceData($resource));
    }

    /**
     * Load resource data
     *
     * @param string $resource Resource filename
     *
     * @return string
     */
    protected function loadResourceData($resource)
    {
        return file_get_contents($resource);
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed       $resource A resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return preg_match('/\.ya?ml$/i', $resource);
    }
}