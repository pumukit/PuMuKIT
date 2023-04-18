<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitCoreExtension extends Extension
{
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
        $container->setParameter('pumukit.downloads', $config['downloads']);
        $container->setParameter('pumukit.masters', $config['masters']);
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
