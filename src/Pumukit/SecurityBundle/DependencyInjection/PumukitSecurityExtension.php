<?php

namespace Pumukit\SecurityBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitSecurityExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_security.cas_url', $config['cas_url']);
        $container->setParameter('pumukit_security.cas_port', $config['cas_port']);
        $container->setParameter('pumukit_security.cas_uri', $config['cas_uri']);
        $container->setParameter('pumukit_security.cas_allowed_ip_clients', $config['cas_allowed_ip_clients']);
        $container->setParameter('pumukit_security.create_users', $config['create_users']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
