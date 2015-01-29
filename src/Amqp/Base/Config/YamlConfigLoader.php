<?php
namespace Amqp\Base\Config;

use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($filename)
    {
        if (file_exists($filename) || !is_readable($filename)) {
            $configurationValues = Yaml::parse(file_get_contents($filename));
        } else {
            throw new Exception("Error: Invalid file descriptor " . $filename);
        }

        return $configurationValues;
    }
}