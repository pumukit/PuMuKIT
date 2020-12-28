<?php

declare(strict_types=1);

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
            ->scalarNode('public_dir')
            ->defaultValue('%kernel.project_dir%/public')
            ->end()
            ->scalarNode('storage_dir')
            ->defaultValue('%kernel.storage_dir%/public/storage')
            ->end()
            ->scalarNode('uploads_dir')
            ->defaultValue('%kernel.project_dir%/public/uploads')
            ->end()
            ->scalarNode('uploads_url')
            ->defaultValue('/uploads')
            ->end()
            ->scalarNode('uploads_material_dir')
            ->defaultValue('%kernel.project_dir%/public/uploads/material')
            ->end()
            ->scalarNode('uploads_pic_dir')
            ->defaultValue('%kernel.project_dir%/public/uploads/pic')
            ->end()
            ->scalarNode('inbox')
            ->defaultValue('%kernel.project_dir%/public/storage/inbox')
            ->end()
            ->scalarNode('tmp')
            ->defaultValue('%kernel.project_dir%/public/storage/tmp')
            ->end()
            ->scalarNode('downloads')
            ->defaultValue('%kernel.project_dir%/public/storage/downloads')
            ->end()
            ->scalarNode('masters')
            ->defaultValue('%kernel.project_dir%/public/storage/masters')
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
