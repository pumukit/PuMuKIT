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
                ->scalarNode('profile')
                    ->defaultValue('video_h264')
                    ->info('Profile name of track')
                ->end()
                ->scalarNode('language')
                    ->defaultValue('en')
                    ->info('Language of track')
                ->end()
                ->scalarNode('description')
                    ->defaultValue('2017 opencast community summit')
                    ->info('Language of track')
                ->end()
            ->end();
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
