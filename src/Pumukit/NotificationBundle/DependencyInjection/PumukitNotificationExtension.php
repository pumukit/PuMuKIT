<?php

namespace Pumukit\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $container->setParameter('pumukit_notification.sender_name', $config['sender_name']);
        $container->setParameter('pumukit_notification.enable_multi_lang', $config['enable_multi_lang']);
        $container->setParameter('pumukit_notification.subject_success', $config['subject_success']);
        $container->setParameter('pumukit_notification.subject_fails', $config['subject_fails']);
        $container->setParameter('pumukit_notification.subject_success_trans', $config['subject_success_trans']);
        $container->setParameter('pumukit_notification.subject_fails_trans', $config['subject_fails_trans']);
        $container->setParameter('pumukit_notification.notificate_errors_to_admin', $config['notificate_errors_to_admin']);

        $env = $container->getParameter('kernel.environment');
        if ('test' === $env) {
            $container->setParameter('pumukit_notification.template', Configuration::TEMPLATE);
            $container->setParameter('pumukit_notification.sender_email', Configuration::SENDER_EMAIL);
            $container->setParameter('pumukit_notification.admin_email', Configuration::ADMIN_EMAIL);
        } else {
            $container->setParameter('pumukit_notification.template', $config['template']);
            $container->setParameter('pumukit_notification.sender_email', $config['sender_email']);
            $container->setParameter('pumukit_notification.admin_email', $config['admin_email']);
        }

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
