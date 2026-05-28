<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitPaellaPlayerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pumukit_paella_player.yaml');

        $container->setParameter('pumukitpaella.force_dual', $config['force_dual']);
        $container->setParameter('pumukitpaella.custom_css_url', $config['custom_css_url']);
        $container->setParameter('pumukitpaella.logo', $config['logo']);
        $container->setParameter('pumukitpaella.xapi_endpoint', $config['xapi_endpoint']);
        $container->setParameter('pumukitpaella.xapi_auth', $config['xapi_auth']);
        $container->setParameter('pumukitpaella.access_control_class', $config['access_control_class']);
        $container->setParameter('pumukitpaella.footprints', $config['footprints']);
        $container->setParameter('pumukitpaella.autoplay', $config['autoplay']);
    }
}
