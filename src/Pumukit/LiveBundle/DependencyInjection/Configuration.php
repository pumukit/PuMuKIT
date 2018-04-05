<?php

namespace Pumukit\LiveBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_live');

        $rootNode
          ->children()
            ->integerNode('chat_update_interval')
              ->defaultValue(5000)
              ->min(5000)
              ->info('The interval in milliseconds to update chat')
            ->end()
            ->integerNode('log_update_interval')
              ->defaultValue(300000)
              ->min(300000)
              ->info('The interval in milliseconds to update log file')
            ->end()
          ->end()
          ;

        return $treeBuilder;
    }
}
