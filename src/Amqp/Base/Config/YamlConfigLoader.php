<?php
namespace Amqp\Base\Config;

use Amqp\Base\Config\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
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
            $loader = new YamlFileLoader(new FileLocator());
            return $loader->load($this->filename);
        }

        throw new Exception("Error: Invalid file descriptor " . $this->filename);
    }
}