<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_base_player');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('secure_secret')
            ->defaultNull()
            ->info('Defines a secret word used to generate authenticated requested links for the ngx_http_secure_link_module. NULL to disable.')
            ->end()
            ->integerNode('secure_duration')
            ->min(0)
            ->defaultValue(3600)
            ->info('The lifetime of a link passed in a request when secure_secret is defined. Default one hour (3600s)')
            ->end()
            ->enumNode('when_dispatch_view_event')
            ->values(['on_load', 'on_play'])
            ->defaultValue('on_play')
            ->info('When dispatch a view event, on load the track file or on play the video (via AJAX request).')
            ->end()
            ->arrayNode('trackfile_without_token')
            ->addDefaultsIfNotSet()
            ->info('Old links to /trackfile that keep working without a valid token. Empty by default.')
            ->children()
            ->arrayNode('series')
            ->info('Ids of the series whose tracks are delivered without a valid token.')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->validate()
            ->ifTrue(static fn (array $ids): bool => \count(array_filter($ids, static fn ($id): bool => !\is_string($id) || 1 !== preg_match('/^[a-f\d]{24}$/D', $id))) > 0)
            ->thenInvalid('Every series must be an id of 24 hexadecimal characters in lowercase.')
            ->end()
            ->end()
            ->arrayNode('referer_hosts')
            ->info('Hosts, without scheme, port or path, whose pages (and subdomains) can open tracks without a valid token. The Referer can be forged: it is not a security measure.')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->beforeNormalization()
            ->ifArray()
            ->then(static fn (array $hosts): array => array_map(static fn ($host) => \is_string($host) ? strtolower($host) : $host, $hosts))
            ->end()
            ->validate()
            ->ifTrue(static fn (array $hosts): bool => \count(array_filter($hosts, static fn ($host): bool => !\is_string($host) || 1 !== preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/D', $host))) > 0)
            ->thenInvalid('Every referer host must be a host name without scheme, port or path, as "example.org".')
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
