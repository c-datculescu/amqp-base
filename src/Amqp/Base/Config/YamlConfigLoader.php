<?php
namespace Amqp\Base\Config;

use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader implements Interfaces\Loader
{
    /**
     * Configuration file name
     * @var string
     */
    protected $filename;

    /**
     * @param string $filename Configuration file name
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }


    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (file_exists($this->filename) && is_readable($this->filename)) {
            $configurationValues = Yaml::parse(file_get_contents($this->filename));
        } else {
            throw new Exception("Error: Invalid file descriptor " . $this->filename);
        }

        return $configurationValues;
    }
}