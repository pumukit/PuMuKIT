<?php

namespace Pumukit\Cmar\WebTVBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitCmarWebTVExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_cmar_web_tv.cas_url', $config['cas_url']);
        $container->setParameter('pumukit_cmar_web_tv.cas_port', $config['cas_port']);
        $container->setParameter('pumukit_cmar_web_tv.cas_uri', $config['cas_uri']);
        $container->setParameter('pumukit_cmar_web_tv.cas_allowed_ip_clients', $config['cas_allowed_ip_clients']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
