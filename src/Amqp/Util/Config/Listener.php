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
        $rootNode = $treeBuilder->root('consumer');

        $rootNode
            ->ignoreExtraKeys()
            ->children()
                ->arrayNode('consumer')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('queue')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
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