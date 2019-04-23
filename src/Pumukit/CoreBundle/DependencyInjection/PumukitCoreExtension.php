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
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit2.info', $config['info']);
        $container->setParameter('pumukit2.locales', $config['locales']);
        $container->setParameter('pumukit2.uploads_dir', $config['uploads_dir']);
        $container->setParameter('pumukit2.uploads_url', $config['uploads_url']);
        $container->setParameter('pumukit2.inbox', $config['inbox']);
        $container->setParameter('pumukit2.tmp', $config['tmp']);
        $container->setParameter('pumukit2.delete_on_disk', $config['delete_on_disk']);
        $container->setParameter('pumukit2.use_series_channels', $config['use_series_channels']);
        $container->setParameter('pumukit.full_magic_url', $config['full_magic_url']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
