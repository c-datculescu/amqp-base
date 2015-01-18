<?php
namespace Amqp\Base\Config;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (file_exists($resource) || !is_readable($resource)) {
            $configurationValues = Yaml::parse(file_get_contents($resource));
        } else {
            throw new Exception("Error: Invalid file descriptor " . $resource);
        }

        return $configurationValues;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && pathinfo($resource, PATHINFO_EXTENSION) === 'yml';
    }
}