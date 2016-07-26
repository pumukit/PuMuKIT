<?php

namespace Pumukit\OaiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_oai');

        $rootNode
            ->children()
              ->booleanNode('use_dc_thumbnail')
                  ->defaultTrue()
                  ->info('Use special tag dc:thumbnail to list the first object thumbnail (deprecated and non standard)')
              ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
