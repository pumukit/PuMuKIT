<?php

namespace Pumukit\SchemaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_schema');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('default_copyright')
            ->defaultValue('')
            ->info('Default copyright MultimediaObject')
            ->end()
            ->scalarNode('default_license')
            ->defaultValue('')
            ->info('Default license MultimediaObject')
            ->end()
            ->scalarNode('default_series_pic')
            ->defaultValue('/bundles/pumukitschema/images/series_folder.png')
            ->info('Default Series picture')
            ->end()
            ->scalarNode('default_playlist_pic')
            ->defaultValue('/bundles/pumukitschema/images/playlist_folder.png')
            ->info('Default Playlist picture')
            ->end()
            ->scalarNode('default_video_pic')
            ->defaultValue('/bundles/pumukitschema/images/video_none.jpg')
            ->info('Default video picture')
            ->end()
            ->scalarNode('default_audio_hd_pic')
            ->defaultValue('/bundles/pumukitschema/images/audio_hd.svg')
            ->info('Default audio HD picture')
            ->end()
            ->scalarNode('default_audio_sd_pic')
            ->defaultValue('/bundles/pumukitschema/images/audio_sd.svg')
            ->info('Default audio SD picture')
            ->end()
            ->booleanNode('enable_add_user_as_person')
            ->defaultTrue()
            ->info('Add logged in User as Person to MultimediaObjects')
            ->end()
            ->scalarNode('personal_scope_role_code')
            ->defaultValue('owner')
            ->info('Role code related to Personal Scope User to use as EmbeddedPerson')
            ->end()
            ->booleanNode('personal_scope_delete_owners')
            ->defaultFalse()
            ->info('Allow Personal Scope users to delete other owners of Series and MultimediaObjects')
            ->end()
            ->booleanNode('gen_user_salt')
            ->defaultTrue()
            ->info('Disable the generation of a random user salt. Required to use PuMuKIT as a CAS user provider.')
            ->end()
            ->arrayNode('external_permissions')
            ->info('External permissions for user profiles.')
            ->prototype('array')
            ->children()
            ->scalarNode('role')
            ->info('Code for the permission. The role Must start with \'ROLE_\'')
            ->isRequired()
            ->end()
            ->scalarNode('description')
            ->isRequired()
            ->end()
            ->arrayNode('dependencies')
            ->info('Dependencies for the given permission. (Other permissions that have to be enabled when this one is.)')
            ->children()
            ->arrayNode('global')
            ->info('Global scope dependencies.')
            ->isRequired()
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('personal')
            ->info('Personal scope dependencies.')
            ->isRequired()
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
