<?php

namespace Pumukit\WorkflowBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_workflow');

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
            ->end()
        ;

        return $treeBuilder;
    }
}
