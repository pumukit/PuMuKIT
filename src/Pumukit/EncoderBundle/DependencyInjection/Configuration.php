<?php

namespace Pumukit\EncoderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Pumukit\EncoderBundle\Services\CpuService;

/**
 * This is the class that validates and merges configuration from your app/config files
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
        $rootNode = $treeBuilder->root('pumukit_encoder');

        //Doc in http://symfony.com/doc/current/components/config/definition.html
        $this->addCpusSection($rootNode);
        $this->addProfilesSection($rootNode);

        return $treeBuilder;
    }


    /**
     * Adds `profiles` section.
     *
     * @param ArrayNodeDefinition $node
     */
    public function addProfilesSection(ArrayNodeDefinition $node) 
    {
    }


    /**
     * Adds `cpu` section.
     *
     * @param ArrayNodeDefinition $node
     */
    public function addCpusSection(ArrayNodeDefinition $node) 
    {
        $node
            ->children()
                ->arrayNode('cpus')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->isRequired()->cannotBeEmpty()
                                ->info('Encoder Hostnames (or IPs)')->end()
                            ->integerNode('number')->min(0)->defaultValue(1)
                                ->info('Maximum number of concurrent encoding jobs')->end()
                            ->integerNode('max')->min(0)->defaultValue(1)
                                ->info('Top for the maximum number of concurrent encoding jobs')->end()
                            ->enumNode('type')->values(array(CpuService::TYPE_LINUX, CpuService::TYPE_WINDOWS, CpuService::TYPE_GSTREAMER))
                                ->defaultValue(CpuService::TYPE_LINUX)
                                ->info('Type of the encoder host (linux, windows or gstreamer)')->end()
                            ->scalarNode('user')
                            ->info('Specifies the user to log in as on the remote encoder host')->end()
                            ->scalarNode('password')
                            ->info('Specifies the user to log in as on the remote encoder host')->end()
                            ->scalarNode('description')
                            ->info('Encoder host description')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
