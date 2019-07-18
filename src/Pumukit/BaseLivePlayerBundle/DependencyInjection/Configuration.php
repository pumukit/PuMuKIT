<?php

namespace Pumukit\BaseLivePlayerBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_base_live_player');

        $rootNode
            ->children()
            ->booleanNode('advance_live_event')
            ->defaultTrue()
            ->info('Activate/Deactivate advanced lives')
            ->end()
            ->scalarNode('event_default_poster')
            ->defaultValue('/bundles/pumukitwebtv/images/live_screen.jpg')
            ->info('Default poster for Advanced Live Events')
            ->end()
            ->scalarNode('advance_live_event_create_default_pic')
            ->defaultValue('/bundles/pumukitwebtv/images/live/live_event_default_pic.jpg')
            ->info('Advance live event session default create from serie pic')
            ->end()
            ->scalarNode('advance_live_event_create_serie_pic')
            ->defaultValue('/bundles/pumukitwebtv/images/live/live_event_series_pic.png')
            ->info('Advance live event session default create serie _pic')
            ->end()
            ->booleanNode('advance_live_event_autocomplete_series')
            ->defaultFalse()
            ->info('Advance live event button to autocomplete series with event data')
            ->end()
            ->booleanNode('liveevent_contact_and_share')
            ->defaultFalse()
            ->info('Shows the advance live event contact form')
            ->end()
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
