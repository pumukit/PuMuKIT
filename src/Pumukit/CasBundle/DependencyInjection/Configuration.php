<?php

namespace Pumukit\CasBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_security');

        $rootNode
            ->children()
            ->scalarNode('cas_url')
            ->isRequired()
            ->info('The hostname of the CAS server')
            ->end()
            ->scalarNode('cas_port')
            ->isRequired()
            ->info('The port the CAS server is running on')
            ->end()
            ->scalarNode('cas_uri')
            ->isRequired()
            ->info('The URI the CAS server is responding on')
            ->end()
            ->arrayNode('cas_allowed_ip_clients')
            ->prototype('scalar')
            ->info('Array of allowed IP clients')
            ->end()
            ->end()
            ->booleanNode('create_users')
            ->defaultTrue()
            ->info('Authorize application to create not found users')
            ->end()
            ->scalarNode('CAS_ID_KEY')
            ->defaultValue('UID')
            ->end()
            ->scalarNode('CAS_CN_KEY')
            ->defaultValue('CN')
            ->end()
            ->scalarNode('CAS_MAIL_KEY')
            ->defaultValue('MAIL')
            ->end()
            ->scalarNode('CAS_GIVENNAME_KEY')
            ->defaultValue('GIVENNAME')
            ->end()
            ->scalarNode('CAS_SURNAME_KEY')
            ->defaultValue('SURNAME')
            ->end()
            ->scalarNode('CAS_GROUP_KEY')
            ->defaultValue('GROUP')
            ->end()
            ->scalarNode('ORIGIN')
            ->defaultValue('cas')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
