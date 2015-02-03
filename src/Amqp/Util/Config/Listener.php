<?php
namespace Amqp\Util\Config;

use Amqp\Base\Config\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Listener implements ConfigurationInterface, NamedConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('listener');

        $rootNode
            ->ignoreExtraKeys()
            ->children()
                ->arrayNode('listener')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('onProcessError')
                                ->defaultValue('continue')
                            ->end()
                            ->integerNode('maxRequeue')
                                ->defaultValue(3)
                            ->end()
                            ->integerNode('bulkAck')
                                ->defaultValue(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'consumer';
    }
}