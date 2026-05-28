<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_paella_player');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('force_dual')
            ->defaultValue(false)
            ->info('If true never send SBS')
            ->end()
            ->scalarNode('custom_css_url')
            ->defaultValue(null)
            ->info('Custom CSS URL')
            ->end()
            ->scalarNode('logo')
            ->defaultValue(null)
            ->info('Custom logo URL')
            ->end()
            ->scalarNode('xapi_endpoint')
            ->defaultValue(null)
            ->info('LRS endpoint for xAPI')
            ->end()
            ->scalarNode('xapi_auth')
            ->defaultValue(null)
            ->info('LRS auth token for xAPI')
            ->end()
            ->scalarNode('access_control_class')
            ->defaultValue('paella.AccessControl')
            ->info('Paella accessControlClass')
            ->end()
            ->scalarNode('footprints')
            ->defaultValue('MHFootPrintsDataDelegate')
            ->info('Paella footprints MHFootPrintsDataDelegate or PaellaFootPrintsDataDelegate')
            ->end()
            ->enumNode('autoplay')
            ->values(['pause_fallback', 'mute_fallback', 'disabled'])
            ->defaultValue('pause_fallback')
            ->info('If "disabled", autoplay is disabled. Else with autoplay, the player behavior when the browser blocks autoplay can be: start playing muted ("mute_fallback") or pause the video ("pause_fallback").')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
