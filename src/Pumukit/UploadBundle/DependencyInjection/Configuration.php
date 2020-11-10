<?php

namespace Pumukit\WizardBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    private $profiles;

    /**
     * Constructor.
     */
    public function __construct(array $profiles)
    {
        $this->profiles = $profiles;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_wizard');
        $rootNode
            ->children()
            ->booleanNode('show_license')
            ->defaultFalse()
            ->info('Enable showing license in first step')
            ->end()
            ->scalarNode('license_dir')
            ->defaultValue('')
            ->info("Path dir of the license files to show in first step if enabled according to locale. E.g.: '%kernel.root_dir%/../src/Pumukit/WizardBundle/Resources/data/license/'. In this folder there should be files named after its locale language: es.txt, en.txt, fr.txt, etc.")
            ->end()
            ->booleanNode('show_tags')
            ->defaultFalse()
            ->info('Enable adding tag to a MultimediaObject in metadata step')
            ->end()
            ->scalarNode('tag_parent_code')
            ->defaultValue('UNESCO')
            ->info('Parent tag code of tags available to add to a Multimedia Object. E.g.: UNESCO')
            ->end()
            ->booleanNode('show_object_license')
            ->defaultFalse()
            ->info('Enable adding license to a MultimediaObject in metadata step. This license is defined in pumukit_schema.license (could be a string or an array).')
            ->end()
            ->booleanNode('mandatory_title')
            ->defaultFalse()
            ->info('Enable to force mandatory title in Series and Multimedia Object steps.')
            ->end()
            ->booleanNode('reuse_series')
            ->defaultFalse()
            ->info('Enable adding new multimedia object to an existing series belonging to the logged in user.')
            ->end()
            ->booleanNode('reuse_admin_series')
            ->defaultFalse()
            ->info('Only valid when parameter reuse_series is set to True. If reuse_admin_series is true, the admin user can reuse only the series he/she created. If reuse_admin_series is set to false, the admin user can reuse any series of PuMuKIT.')
            ->end()
            ->booleanNode('show_simple_mm_title')
            ->defaultFalse()
            ->info('Enable showing Multimedia Object title in Simple Wizard form.')
            ->end()
            ->booleanNode('show_simple_series_title')
            ->defaultFalse()
            ->info('Enable showing Series title in Simple Wizard form.')
            ->end()
            ->enumNode('simple_default_master_profile')
            ->values($this->profiles)
            ->info('Force a default master profile for multimedia objects created using the simple wizard (used by Moodle or by OpenEdx)')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
