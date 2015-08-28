<?php

namespace Pumukit\LDAPBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_ldap');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
          ->children()
            ->scalarNode('server')
              ->isRequired()
              ->info('LDAP Server DNS address')
            ->end()
            ->scalarNode('bind_rdn')
              ->isRequired()
              ->info('LDAP Server DN Search Engine')
            ->end()
            ->scalarNode('bind_password')
              ->isRequired()
              ->info('LDAP Server password')
            ->end()
            ->scalarNode('base_dn')
              ->isRequired()
              ->info('LDAP Server DN User')
            ->end()
          ->end();

        return $treeBuilder;
    }
}
