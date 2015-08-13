<?php

namespace Pumukit\MoodleBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_moodle');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
          ->children()
            ->scalarNode('password')
              ->defaultValue('ThisIsASecretPasswordChangeMe')
              ->info('shared secret  between Moodle and Pumukit')
            ->end()
          ->end()
          ->children()
            ->scalarNode('role')
              ->defaultValue('actor')
              ->info('Role used to filter persons in multimedia object')
            ->end()
          ->end()
        ;


        return $treeBuilder;
    }
}
