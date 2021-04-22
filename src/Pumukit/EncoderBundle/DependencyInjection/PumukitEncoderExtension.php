<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitEncoderExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukitencode.delete_inbox_files', $config['delete_inbox_files']);
        $container->setParameter('pumukitencode.max_execution_job_seconds', $config['max_execution_job_seconds']);
        $container->setParameter('pumukitencode.cpulist', $config['cpus']);
        $container->setParameter('pumukitencode.profilelist', $config['profiles']);
        $container->setParameter('pumukitencode.target_default_profiles', $config['target_default_profiles']);
        $container->setParameter('pumukitencode.thumbnail.width', $config['thumbnail']['width']);
        $container->setParameter('pumukitencode.thumbnail.height', $config['thumbnail']['height']);
    }
}
