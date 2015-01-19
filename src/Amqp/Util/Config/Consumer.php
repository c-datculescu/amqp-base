<?php
namespace Amqp\Util\Config;

use Amqp\Base\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Consumer implements ConfigurationInterface, NamedConfigInterface
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
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
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
                            ->arrayNode('watchers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('class')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->arrayNode('arguments')
                                            ->prototype('scalar')->end()
                                        ->end()
                                    ->end()
                                ->end()
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