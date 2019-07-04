<?php

namespace Pumukit\PodcastBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitPodcastExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_podcast.channel_title', $config['channel_title']);
        $container->setParameter('pumukit_podcast.channel_description', $config['channel_description']);
        $container->setParameter('pumukit_podcast.channel_copyright', $config['channel_copyright']);
        $container->setParameter('pumukit_podcast.itunes_category', $config['itunes_category']);
        $container->setParameter('pumukit_podcast.itunes_summary', $config['itunes_summary']);
        $container->setParameter('pumukit_podcast.itunes_subtitle', $config['itunes_subtitle']);
        $container->setParameter('pumukit_podcast.itunes_author', $config['itunes_author']);
        $container->setParameter('pumukit_podcast.itunes_explicit', $config['itunes_explicit']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
