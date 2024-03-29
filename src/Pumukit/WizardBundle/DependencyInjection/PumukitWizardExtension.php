<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitWizardExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration(array_keys($container->getParameter('pumukitencode.profilelist')));
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_wizard.show_license', $config['show_license']);
        $container->setParameter('pumukit_wizard.license_dir', $config['license_dir']);
        $container->setParameter('pumukit_wizard.show_tags', $config['show_tags']);
        $container->setParameter('pumukit_wizard.tag_parent_code', $config['tag_parent_code']);
        $container->setParameter('pumukit_wizard.show_object_license', $config['show_object_license']);
        $container->setParameter('pumukit_wizard.mandatory_title', $config['mandatory_title']);
        $container->setParameter('pumukit_wizard.reuse_series', $config['reuse_series']);
        $container->setParameter('pumukit_wizard.reuse_admin_series', $config['reuse_admin_series']);
        $container->setParameter('pumukit_wizard.show_simple_mm_title', $config['show_simple_mm_title']);
        $container->setParameter('pumukit_wizard.show_simple_series_title', $config['show_simple_series_title']);

        if (isset($config['simple_default_master_profile'])) {
            $container->setParameter('pumukit_wizard.simple_default_master_profile', $config['simple_default_master_profile']);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration(array_keys($container->getParameter('pumukitencode.profilelist')));
    }
}
