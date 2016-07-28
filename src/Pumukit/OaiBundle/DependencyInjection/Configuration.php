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
              ->scalarNode('video_dc_type')
                  ->defaultValue('Moving Image')
                  ->info('DublinCore type for video contents. See http://dublincore.org/documents/dcmi-type-vocabulary/#H7')
              ->end()
              ->scalarNode('audio_dc_type')
                  ->defaultValue('Sound')
                  ->info('DublinCore type for audio contents. See http://dublincore.org/documents/dcmi-type-vocabulary/#H7')
              ->end()
              ->booleanNode('use_license_as_dc_rights')
                  ->defaultFalse()
                  ->info('Use the object license as dc:rights')
              ->end()
              ->enumNode('dc_subject_format')
                  ->values(array('all', 'code', 'title', 'e-ciencia'))
                  ->defaultValue('title')
                  ->info('Format used with dc:subject. All: "120000 - Mathematics", Code: "120000", Title: "Mathematics", E-ciencia: "12 Matemáticas"')
              ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
