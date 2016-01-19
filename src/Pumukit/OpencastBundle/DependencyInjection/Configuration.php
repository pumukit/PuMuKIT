<?php

namespace Pumukit\OpencastBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_opencast');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
          ->children()
            ->scalarNode('host')
              ->info('Matterhorn server URL (Engage node in cluster).')
            ->end()
            ->scalarNode('username')
              ->defaultValue('')
              ->info('Name of the account used to operate the Matterhron REST endpoints (org.opencastproject.security.digest.user).')
            ->end()
            ->scalarNode('password')
              ->defaultValue('')
              ->info('Password for the account used to operate the Matterhorn REST endpoints (org.opencastproject.security.digest.pass).')
            ->end()
            ->scalarNode('player')
              ->defaultValue('/engage/ui/watch.html')
              ->info('Opencast player URL or path (default /engage/ui/watch.html).')
            ->end()
            ->booleanNode('generate_sbs')
              ->defaultFalse()
              ->info('Genereate side by side video when MP is imported')
            ->end()
            ->scalarNode('profile')
              ->defaultValue('sbs')
              ->info('Profile name to generate the side by side video.')
            ->end()
            ->booleanNode('scheduler_on_menu')
              ->defaultFalse()
              ->info('List a opencast scheduler link on the menu.')
            ->end()
            ->scalarNode('scheduler')
              ->defaultValue('/admin/index.html#/recordings')
              ->info('Opencast schedule URL or path (default /admin/index.html#/recordings).')
            ->end()
            ->booleanNode('dashboard_on_menu')
              ->defaultFalse()
              ->info('List a galicaster dashboard link on the menu.')
            ->end()
            ->scalarNode('dashboard')
              ->defaultValue('/dashboard/index.html')
              ->info('Galicaster dashboard URL or path (default /dashboard/index.html).')
            ->end()
            ->arrayNode('url_mapping')
              ->prototype('array')
              ->info('URLs and paths used to mapping the Opencast share.')
              ->children()
                ->scalarNode('url')->isRequired()->end()
                ->scalarNode('path')->isRequired()->end()
              ->end()
            ->end()
          ->end()
        ;
          

        return $treeBuilder;
    }
}
