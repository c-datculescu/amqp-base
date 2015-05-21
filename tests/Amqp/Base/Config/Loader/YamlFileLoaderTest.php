<?php

namespace Test\Amqp\Base\Config\Loader;

use Amqp\Base\Config\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test configuration load with imports
     */
    public function testLoadWithImports()
    {

        $mockLoader = $this->getMockBuilder('Amqp\Base\Config\Loader\YamlFileLoader')
            ->disableOriginalConstructor()
            ->setMethods([
                'loadResourceData'
            ])->getMock();

        $mockLoader->expects($this->any())->method('loadResourceData')->willReturnCallback(function ($filename) {
            if ('test.yml' === $filename) {
                return <<<YAML
imports:
    - { resource: "foo.yml" }
test:
    1
YAML;
            }

            if ('foo.yml' === $filename) {
                return <<<YAML
foo_string: "foo_string"
foo_arr:
    - 1
    - 2
    - { name: "test" }
YAML;
            }
        });

        $expected = [
            'foo_string' => 'foo_string',
            'foo_arr' => [1, 2, ['name' => 'test']],
            'test' => 1,
        ];

        $config = $mockLoader->load('test.yml');
        $this->assertEquals($config, $expected);
    }
}