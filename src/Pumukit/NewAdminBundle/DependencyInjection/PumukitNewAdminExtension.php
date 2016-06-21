<?php

namespace Pumukit\NewAdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitNewAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_new_admin.disable_broadcast_creation', $config['disable_broadcast_creation']);
        $container->setParameter('pumukit_new_admin.licenses', $config['licenses']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($container->hasParameter('pumukit2.naked_backoffice_domain')) {
            $definition = new Definition('Pumukit\NewAdminBundle\EventListener\NakedBackofficeListener',
                array('%pumukit2.naked_backoffice_domain%', '%pumukit2.naked_backoffice_background%'));

            $definition->addTag('kernel.event_listener', array('event' => 'kernel.controller', 'method' => 'onKernelController'));
            $container->setDefinition('pumukitnewadmin.nakedbackoffice', $definition);
        }
    }
}
