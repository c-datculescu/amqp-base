<?php
namespace Amqp\Util\Config;

use Amqp\Base\Interfaces\NamedConfigInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Publisher implements ConfigurationInterface, NamedConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('publisher');
        $rootNode
            ->ignoreExtraKeys()
            ->children()
                ->arrayNode('publisher')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('exchange')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('timeout')
                                ->defaultValue(0)
                            ->end()
                                ->integerNode('maxRequeue')
                                ->defaultValue(3)
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
        return 'publisher';
    }
}