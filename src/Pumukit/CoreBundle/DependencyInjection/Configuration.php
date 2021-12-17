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
            ->scalarNode('inboxUploadURL')
            ->defaultValue('tus')
            ->info('URL to process uploaded files. Ex: https://localhost/tus')
            ->end()
            ->scalarNode('inboxUploadLIMIT')
            ->defaultValue(5)
            ->info('Max number of files to upload at the same time')
            ->end()
            ->scalarNode('minFileSize')
            ->defaultValue('1MB')
            ->info('Minimum file size in bytes for each')
            ->end()
            ->scalarNode('maxNumberOfFiles')
            ->defaultValue(5)
            ->info('Total number of files that can be selected')
            ->end()
            ->scalarNode('maxFileSize')
            ->defaultValue('20GB')
            ->info('Maximum file size in bytes for each individual file')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
