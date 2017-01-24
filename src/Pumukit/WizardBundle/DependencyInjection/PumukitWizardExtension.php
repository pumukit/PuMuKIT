<?php

namespace Pumukit\WizardBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitWizardExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_wizard.show_license', $config['show_license']);
        $container->setParameter('pumukit_wizard.license_dir', $config['license_dir']);
        $container->setParameter('pumukit_wizard.show_tags', $config['show_tags']);
        $container->setParameter('pumukit_wizard.tag_parent_code', $config['tag_parent_code']);
        $container->setParameter('pumukit_wizard.show_object_license', $config['show_object_license']);
        $container->setParameter('pumukit_wizard.mandatory_title', $config['mandatory_title']);
        $container->setParameter('pumukit_wizard.reuse_series', $config['reuse_series']);
        $container->setParameter('pumukit_wizard.reuse_admin_series', $config['reuse_admin_series']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
