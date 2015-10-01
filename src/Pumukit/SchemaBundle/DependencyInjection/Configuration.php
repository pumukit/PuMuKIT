<?php

namespace Pumukit\SchemaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_schema');

        $rootNode
          ->children()
            ->scalarNode('default_series_pic')
              ->defaultValue('/images/series_folder.png')
              ->info('Default Series picture')
            ->end()
            ->scalarNode('default_video_pic')
              ->defaultValue('/images/video_none.jpg')
              ->info('Default video picture')
            ->end()
            ->scalarNode('default_audio_hd_pic')
              ->defaultValue('/images/audio_hd.svg')
              ->info('Default audio HD picture')
            ->end()
            ->scalarNode('default_audio_sd_pic')
              ->defaultValue('/images/audio_sd.svg')
              ->info('Default audio SD picture')
            ->end()
          ->end();

        return $treeBuilder;
    }
}
