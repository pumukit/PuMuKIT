<?php

namespace Pumukit\WizardBundle\DependencyInjection;

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
	  ->end();
	  

        return $treeBuilder;
    }
}
