<?php

namespace Pumukit\CasBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitCasExtension extends Extension
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

        $container->setParameter('pumukit_security.cas_id_key', $config['CAS_ID_KEY']);
        $container->setParameter('pumukit_security.cas_cn_key', $config['CAS_CN_KEY']);
        $container->setParameter('pumukit_security.cas_mail_key', $config['CAS_MAIL_KEY']);
        $container->setParameter('pumukit_security.cas_givenname_key', $config['CAS_GIVENNAME_KEY']);
        $container->setParameter('pumukit_security.cas_surname_key', $config['CAS_SURNAME_KEY']);
        $container->setParameter('pumukit_security.cas_group_key', $config['CAS_GROUP_KEY']);
        $container->setParameter('pumukit_security.cas_origin_key', $config['ORIGIN']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
