<?php

namespace Pumukit\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_core');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('info')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('title')
            ->defaultValue('UPumukitTV')
            ->end()
            ->scalarNode('description')
            ->defaultValue('Pumukit University TV Website')
            ->end()
            ->scalarNode('keywords')
            ->defaultValue('webtv, Tv, Pumukit University, uvigo, uvigotv, pumukit')
            ->end()
            ->scalarNode('email')
            ->defaultValue('tv@pumukit.tv')
            ->end()
            ->scalarNode('logo')
            ->defaultValue('/bundles/pumukitwebtv/images/webtv/logo80px.png')
            ->end()
            ->end()
            ->end()
            ->arrayNode('locales')
            ->info('Languages of WebTV')
            ->prototype('scalar')->end()
            ->defaultValue(['en', 'es'])
            ->end()
            ->scalarNode('uploads_dir')
            ->defaultValue('%kernel.root_dir%/../web/uploads')
            ->end()
            ->scalarNode('uploads_url')
            ->defaultValue('/uploads')
            ->end()
            ->scalarNode('inbox')
            ->defaultValue('%kernel.root_dir%/../web/storage/inbox')
            ->end()
            ->scalarNode('tmp')
            ->defaultValue('%kernel.root_dir%/../web/storage/tmp')
            ->end()
            ->booleanNode('delete_on_disk')
            ->defaultTrue()
            ->end()
            ->booleanNode('use_series_channels')
            ->defaultFalse()
            ->end()
            ->booleanNode('full_magic_url')
            ->defaultFalse()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
