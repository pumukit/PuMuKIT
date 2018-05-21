<?php

namespace Pumukit\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit2.info', $config['pumukit_core.info']);
        $container->setParameter('pumukit2.locales', $config['pumukit_core.locales']);
        $container->setParameter('pumukit2.uploads_dir', $config['pumukit_core.uploads_dir']);
        $container->setParameter('pumukit2.uploads_url', $config['pumukit_core.uploads_url']);
        $container->setParameter('pumukit2.inbox', $config['pumukit_core.inbox']);
        $container->setParameter('pumukit2.tmp', $config['pumukit_core.tmp']);
        $container->setParameter('pumukit2.delete_on_disk', $config['pumukit_core.delete_on_disk']);
        $container->setParameter('pumukit2.use_series_channels', $config['pumukit_core.use_series_channels']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
