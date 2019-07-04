<?php

namespace Pumukit\BasePlayerBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_base_player');

        $rootNode
          ->children()
            ->scalarNode('secure_secret')
              ->defaultNull()
              ->info('Defines a secret word used to generate authenticated requested links for the ngx_http_secure_link_module. NULL to disable.')
            ->end()
            ->integerNode('secure_duration')
              ->min(0)
              ->defaultValue(3600)
              ->info('The lifetime of a link passed in a request when secure_secret is defined. Default one hour (3600s)')
            ->end()
            ->enumNode('when_dispatch_view_event')
              ->values(['on_load', 'on_play'])
              ->defaultValue('on_load')
              ->info('When dispatch a view event, on load the track file or on play the video (via AJAX request).')
            ->end()
          ->end();

        return $treeBuilder;
    }
}
