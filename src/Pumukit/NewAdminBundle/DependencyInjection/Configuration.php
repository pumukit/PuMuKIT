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
            ->booleanNode('advance_live_event')
            ->defaultTrue()
            ->info('Advance live event session')
            ->end()
            ->booleanNode('show_menu_place_and_precinct')
            ->defaultFalse()
            ->info('Show separated menu places and precinct')
            ->end()
            ->scalarNode('advance_live_event_create_serie_pic')
            ->defaultValue('/bundles/pumukitnewadmin/images/live_event_series_pic.png')
            ->info('Advance live event session default create serie _pic')
            ->end()
            ->scalarNode('advance_live_event_create_default_pic')
            ->defaultValue('/bundles/pumukitnewadmin/images/live_event_default_pic.jpg')
            ->info('Advance live event session default create from serie pic')
            ->end()
            ->booleanNode('advance_live_event_autocomplete_series')
            ->defaultFalse()
            ->info('Advance live event button to autocomplete series with event data')
            ->end()
            ->booleanNode('liveevent_contact_and_share')
            ->defaultFalse()
            ->info('Shows the advance live event contact form')
            ->end()
            ->scalarNode('multimedia_object_label')
            ->defaultValue('Multimedia Objects')
            ->info('Name of the label of the list of Multimedia Objects in Menu Builder and in the title of the page')
            ->end()
            ->booleanNode('show_naked_pub_tab')
            ->defaultFalse()
            ->info('if true, it shows a simplified publication tab on the naked view')
            ->end()
            ->arrayNode('licenses')
            ->prototype('scalar')
            ->info('List of licenses used for series and multimedia objects. Text input used if empty')
            ->end()
            ->end()
            ->scalarNode('base_catalogue_tag')
            ->defaultValue(null)
            ->info('Name of the tag to use on catalogue')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
