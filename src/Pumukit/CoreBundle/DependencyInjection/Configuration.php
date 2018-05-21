<?php

namespace Pumukit\CoreBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_core');

        $rootNode
            ->children()
                ->arrayNode('info')
                    ->info('Info of PuMuKIT')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('title')
                                ->defaultValue('Title')
                            ->end()
                            ->scalarNode('description')
                                ->defaultValue('Description')
                            ->end()
                            ->scalarNode('keywords')
                                ->defaultValue('Keywords')
                            ->end()
                            ->scalarNode('email')
                                ->defaultValue('Email')
                            ->end()
                            ->scalarNode('logo')
                                ->defaultValue('Logo')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('locales')
                    ->info('Languages of WebTV')
                    ->prototype('scalar')
                    ->defaultValue('en')
                    ->end()
                ->end()
                ->scalarNode('uploads_dir')
                    ->defaultValue('%kernel.root_dir%/../web/uploads')
                ->end()
                ->scalarNode('uploads_url')
                    ->defaultValue('/uploads')
                ->end()
                ->scalarNode('inbox')
                    ->defaultValue('/mnt/inbox')
                ->end()
                ->scalarNode('tmp')
                    ->defaultValue('/mnt/tmp')
                ->end()
                ->booleanNode('delete_on_disk')
                    ->defaultTrue()
                ->end()
                ->booleanNode('use_series_channels')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
