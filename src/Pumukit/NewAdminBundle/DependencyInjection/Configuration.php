<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_new_admin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('show_menu_place_and_precinct')
            ->defaultFalse()
            ->info('Show separated menu places and precinct')
            ->end()
            ->booleanNode('enable_playlist')
            ->defaultFalse()
            ->info('Enable playlist menu')
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
            ->info('Code of the tag to use on catalogue')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
