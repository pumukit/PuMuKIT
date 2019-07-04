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
            ->arrayNode('chat')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enable')->isRequired()
            ->defaultFalse()
            ->info('Enable chat in live channel')
            ->end()
            ->integerNode('update_interval')
            ->defaultValue(5000)
            ->info('Interval in milliseconds to refresh the content of the chat.')
            ->end()
            ->end()
            ->end()
            ->arrayNode('twitter')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enable')->isRequired()
            ->defaultFalse()
            ->info('Enable Twitter in live channel')
            ->end()
            ->scalarNode('accounts_link_color')
            ->defaultValue('#3b94d9')
            ->info('The text color of the accounts links in tweets when hovering. Default value: Twitter default text color #3b94d9')
            ->end()
            ->end()
            ->end()
            ->end()
          ;

        return $treeBuilder;
    }
}
