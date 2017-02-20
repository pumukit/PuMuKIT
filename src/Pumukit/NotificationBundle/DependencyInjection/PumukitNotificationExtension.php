<?php

namespace Pumukit\NotificationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitNotificationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_notification.enable', $config['enable']);
        $container->setParameter('pumukit_notification.platform_name', $config['platform_name']);
        $container->setParameter('pumukit_notification.sender_email', $config['sender_email']);
        $container->setParameter('pumukit_notification.sender_name', $config['sender_name']);
        $container->setParameter('pumukit_notification.notificate_errors_to_sender', $config['notificate_errors_to_sender']);
        $container->setParameter('pumukit_notification.admin_email', $config['admin_email']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
