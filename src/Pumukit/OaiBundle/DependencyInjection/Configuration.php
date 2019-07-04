<?php

namespace Pumukit\OaiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
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
              ->booleanNode('list_only_published_objects')
                  ->defaultTrue()
                  ->info('List only multimedia objects in published status')
              ->end()
              ->scalarNode('pub_channel_tag')
                  ->defaultValue('PUCHWEBTV')
                  ->info('The pub_channel_tag parameter used in the frontend filter')
              ->end()
              ->scalarNode('display_track_tag')
                  ->defaultValue('display')
                  ->info('The display_track_tag parameter used in the frontend filter')
              ->end()
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
              ->scalarNode('role_for_dc_creator')
                  ->defaultValue('actor')
                  ->info('PuMuKIT role used to get the dc:creator. Null to use all the roles')
              ->end()
              ->booleanNode('use_license_as_dc_rights')
                  ->defaultFalse()
                  ->info('Use the object license as dc:rights')
              ->end()
              ->booleanNode('use_copyright_as_dc_publisher')
                  ->defaultFalse()
                  ->info('Use the object copyright as dc:publisher')
              ->end()
              ->enumNode('dc_subject_format')
                  ->values(['all', 'code', 'title', 'e-ciencia'])
                  ->defaultValue('title')
                  ->info('Format used with dc:subject. All: "120000 - Mathematics", Code: "120000", Title: "Mathematics", E-ciencia: "12 Matemáticas"')
              ->end()
              ->enumNode('dc_identifier_url_mapping')
                  ->values(['all', 'portal_and_track', 'portal', 'iframe', 'track'])
                  ->defaultValue('portal')
                  ->info('URL used with dc:identifier. URL to delivery track (.mp4), URL to iframe (.html), URL to portal (.html) or a combination')
              ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
