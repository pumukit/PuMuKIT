<?php

namespace Pumukit\PodcastBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_podcast');

        $rootNode
            ->children()
            ->scalarNode('channel_title')
            ->info('Title of the channel')
            ->defaultNull()
            ->end()
            ->scalarNode('channel_description')
            ->info('Description of the channel')
            ->defaultNull()
            ->end()
            ->scalarNode('channel_copyright')
            ->info('Copyright of the channel')
            ->defaultNull()
            ->end()
            ->scalarNode('itunes_category')
            ->info('Itunes category of the channel. Default value: Education. This value must be in English: https://validator.w3.org/feed/docs/error/InvalidItunesCategory.html')
            ->defaultValue('Education')
            ->end()
            ->scalarNode('itunes_summary')
            ->info('Itunes summary of the channel')
            ->defaultNull()
            ->end()
            ->scalarNode('itunes_subtitle')
            ->info('Itunes subtitle of the channel')
            ->defaultNull()
            ->end()
            ->scalarNode('itunes_author')
            ->info('Itunes author of the channel. Default value: PuMuKIT-TV')
            ->defaultValue('PuMuKIT-TV')
            ->end()
            ->booleanNode('itunes_explicit')
            ->info('Itunes is explicit. Default value: false')
            ->defaultValue(false)
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
