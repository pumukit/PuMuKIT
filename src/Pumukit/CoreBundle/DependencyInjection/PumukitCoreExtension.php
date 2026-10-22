<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class PumukitCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $importmapDir = dirname(__DIR__).'/Resources/importmap';

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [$importmapDir.'/vendor' => 'vendor'],
                'importmap_path' => $importmapDir.'/importmap.php',
                'vendor_dir' => $importmapDir.'/vendor',
                'importmap_polyfill' => false,
            ],
        ]);

        if ($container->hasExtension('stimulus')) {
            $container->prependExtensionConfig('stimulus', [
                'controllers_json' => $importmapDir.'/controllers.json',
            ]);
        }

        if ($container->hasExtension('ux_translator')) {
            $container->prependExtensionConfig('ux_translator', [
                'domains' => ['messages'],
                'dump_typescript' => false,
            ]);
        }

        FrontendAssets::register($container, dirname(__DIR__), '@pumukit/core');
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit.info', $config['info']);
        $container->setParameter('pumukit.locales', $config['locales']);
        $container->setParameter('pumukit.public_dir', $config['public_dir']);
        $container->setParameter('pumukit.storage_dir', $config['storage_dir']);
        $container->setParameter('pumukit.uploads_dir', $config['uploads_dir']);
        $container->setParameter('pumukit.uploads_url', $config['uploads_url']);
        $container->setParameter('pumukit.uploads_material_dir', $config['uploads_material_dir']);
        $container->setParameter('pumukit.uploads_pic_dir', $config['uploads_pic_dir']);
        $container->setParameter('pumukit.inbox', $config['inbox']);
        $container->setParameter('pumukit.tmp', $config['tmp']);
        $container->setParameter('pumukit.downloads_dir', $config['downloads']);
        $container->setParameter('pumukit.masters_dir', $config['masters']);
        $container->setParameter('pumukit.delete_on_disk', $config['delete_on_disk']);
        $container->setParameter('pumukit.use_series_channels', $config['use_series_channels']);
        $container->setParameter('pumukit.full_magic_url', $config['full_magic_url']);
        $container->setParameter('pumukit.inboxDebug', $config['inboxDebug']);
        $container->setParameter('pumukit.inboxUploadURL', $config['inboxUploadURL']);
        $container->setParameter('pumukit.inboxUploadLIMIT', $config['inboxUploadLIMIT']);
        $container->setParameter('pumukit.maxFileSize', $config['maxFileSize']);
        $container->setParameter('pumukit.minFileSize', $config['minFileSize']);
        $container->setParameter('pumukit.maxNumberOfFiles', $config['maxNumberOfFiles']);
        $container->setParameter('pumukit.overridePatchMethod', $config['inboxOverridePatchMethod']);
        $container->setParameter('pumukit.inboxProgressBarColor', $config['inboxProgressBarColor']);
        $container->setParameter('pumukit.showBackofficeButtonInInbox', $config['showBackofficeButtonInInbox']);
    }
}
