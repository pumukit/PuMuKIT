<?php

namespace Pumukit\StatsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_stats');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('use_aggregation')
            ->defaultFalse()
            ->info('Use ViewsAggregation instead ViewsLog for generate stats (See PumukitAggregateCommand).')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
