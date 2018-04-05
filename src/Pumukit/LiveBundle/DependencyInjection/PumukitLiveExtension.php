<?php

namespace Pumukit\LiveBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitLiveExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('monolog', array(
            'channels' => array('live'),
            'handlers' => array(
                'privatelive' => array(
                    'type' => 'stream',
                    'path' => "%kernel.logs_dir%/live_%kernel.environment%.log",
                    'level' => 'info',
                    'channels' => array('live')
                )
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('pumukit_live.chat_update_interval', $config['chat_update_interval']);
        $container->setParameter('pumukit_live.log_update_interval', $config['log_update_interval']);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
