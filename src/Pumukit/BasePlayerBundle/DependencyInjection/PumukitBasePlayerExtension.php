<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class PumukitBasePlayerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukitplayer.secure_secret', $config['secure_secret']);
        $container->setParameter('pumukitplayer.secure_duration', $config['secure_duration']);
        $container->setParameter('pumukitplayer.when_dispatch_view_event', $config['when_dispatch_view_event']);
    }
}
