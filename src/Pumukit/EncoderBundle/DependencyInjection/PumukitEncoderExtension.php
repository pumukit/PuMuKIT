<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitEncoderExtension extends Extension
{
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
