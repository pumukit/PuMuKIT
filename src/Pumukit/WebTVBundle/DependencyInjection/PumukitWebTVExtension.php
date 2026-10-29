<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\DependencyInjection;

use Pumukit\CoreBundle\DependencyInjection\FrontendAssets;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class PumukitWebTVExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        FrontendAssets::register($container, dirname(__DIR__), '@pumukit/webtv');

        $container->prependExtensionConfig('framework', [
            'html_sanitizer' => [
                'sanitizers' => [
                    'pumukit.series_html' => [
                        'allow_safe_elements' => true,
                        'allow_attributes' => ['class' => '*', 'style' => '*', 'target' => 'a'],
                        'allowed_link_schemes' => ['http', 'https', 'mailto'],
                        'allowed_media_schemes' => ['http', 'https'],
                        'allow_relative_links' => true,
                        'allow_relative_medias' => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
    }
}
