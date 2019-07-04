<?php

namespace Pumukit\LiveBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_live.chat', $config['chat']);
        $container->setParameter('pumukit_live.chat.enable', $config['chat']['enable']);
        $container->setParameter('pumukit_live.chat.update_interval', $config['chat']['update_interval']);
        $container->setParameter('pumukit_live.twitter', $config['twitter']);
        $container->setParameter('pumukit_live.twitter.enable', $config['twitter']['enable']);
        $container->setParameter('pumukit_live.twitter.accounts_link_color', $config['twitter']['accounts_link_color']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
