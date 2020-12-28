<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_workflow');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->booleanNode('auto_extract_pic')
            ->defaultTrue()
            ->info('Extract thumbnail automatically')
            ->end()
            ->scalarNode('auto_extract_pic_percentage')
            ->defaultValue('50%')
            ->info('Extract thumbnail automatically on this percentage')
            ->end()
            ->booleanNode('dynamic_pic_extract')
            ->defaultTrue()
            ->info('Extract dynamic pic thumbnail automatically')
            ->end()
            ->scalarNode('dynamic_pic_extract_track_tag_allowed')
            ->defaultValue('master')
            ->info('Extract thumbnail automatically on this percentage')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
