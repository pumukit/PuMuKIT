<?php

namespace Pumukit\NewAdminBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_new_admin');

        $rootNode
          ->children()
            ->booleanNode('disable_broadcast_creation')
              ->defaultTrue()
              ->info('Disable the creation of new Broadcasts')
            ->end()
            ->arrayNode('licenses')
              ->prototype('scalar')
              ->info('List of licenses used for series and multimedia objects. Text input used if empty')
            ->end()
          ->end();

        return $treeBuilder;
    }
}
