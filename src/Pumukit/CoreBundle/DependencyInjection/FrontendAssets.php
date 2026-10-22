<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FrontendAssets
{
    public static function register(ContainerBuilder $container, string $bundleDir, string $namespace): void
    {
        $assetsDir = $bundleDir.'/Resources/assets';
        if (!is_dir($assetsDir)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [$assetsDir => $namespace],
            ],
        ]);

        if (is_dir($assetsDir.'/controllers') && $container->hasExtension('stimulus')) {
            $container->prependExtensionConfig('stimulus', [
                'controller_paths' => [$assetsDir.'/controllers'],
            ]);
        }
    }
}
