<?php

namespace Pumukit\OpencastBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('pumukit_opencast');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
            ->scalarNode('host')
            ->isRequired()
            ->info('Opencast server URL (Engage node in cluster).')
            ->end()
            ->scalarNode('admin_host')
            ->defaultNull()
            ->info('Opencast admin server URL. Optional and used to avoid extra queries')
            ->end()
            ->scalarNode('username')
            ->defaultValue('')
            ->info('Name of the account used to operate the Opencast REST endpoints (org.opencastproject.security.digest.user).')
            ->end()
            ->scalarNode('password')
            ->defaultValue('')
            ->info('Password for the account used to operate the Opencast REST endpoints (org.opencastproject.security.digest.pass).')
            ->end()
            ->scalarNode('player')
            ->defaultValue('/engage/ui/watch.html')
            ->info('Opencast player URL or path (default /engage/ui/watch.html).')
            ->end()
            ->booleanNode('use_redirect')
            ->defaultTrue()
            ->info('Video will be redirected for being played at Opencast server, otherwise it will be iframed in Pumukit.')
            ->end()
            ->booleanNode('error_if_file_not_exist')
            ->defaultTrue()
            ->info('Throw an error if the track file doesn\'t exist or it is not accessible using the url_mapping info.')
            ->end()
            ->booleanNode('batchimport_inverted')
            ->defaultFalse()
            ->info('Opencast videos will be imported with presentation and presented inverted, switching positions, if set to true.')
            ->end()
            ->booleanNode('show_importer_tab')
            ->defaultTrue()
            ->info('Opencast Importer Tab will be shown. If importation is done using batch import, set this parameter to false to hide the Opencast Importer Tab.')
            ->end()
            ->booleanNode('delete_archive_mediapackage')
            ->defaultFalse()
            ->info('Opencast mediapackage will be deleted from the archive when the PuMuKIT track or multimedia object is deleted, when set to true.')
            ->end()
            ->scalarNode('deletion_workflow_name')
            ->defaultValue('delete-archive')
            ->info('Name of the workflow in Opencast that handles the deletion of a mediapackage from the archive.')
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
            ->booleanNode('manage_opencast_users')
            ->defaultFalse()
            ->info('Permission to create/modify/delete Opencast users from PuMuKIT.')
            ->end()
            ->booleanNode('sync_series_with_opencast')
            ->defaultFalse()
            ->info('Permission to sync PuMuKIT series to Opencast. PuMuKIT will be the master node here.')
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
            ->arrayNode('sbs')
            ->info('Side By Side configuration')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('generate_sbs')
            ->defaultFalse()
            ->info('Genereate side by side video when MP is imported')
            ->end()
            ->scalarNode('profile')
            ->defaultValue('sbs')
            ->info('Profile name to generate the side by side video.')
            ->end()
            ->booleanNode('use_flavour')
            ->defaultTrue()
            ->info('Use SBS flavour given by sbs_flavour parameter.')
            ->end()
            ->scalarNode('flavour')
            ->defaultValue('composition/delivery')
            ->info('Opencast flavour name of the track to be used as SBS video.')
            ->end()
            ->end()
            ->end()
            ->scalarNode('default_tag_imported')
            ->defaultValue('TECHOPENCAST')
            ->info('Set default tag when imported from opencast to PMK2')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
