<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DependencyInjection;

use App\Shared\Infrastructure\Security\Permission\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPermissionsPass implements CompilerPassInterface
{
    private const PERMISSION_REGISTRY_SERVICE_ID = PermissionRegistry::class;
    private const PERMISSION_REGISTER_TAG = 'pumukit.permission_register';

    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds(self::PERMISSION_REGISTER_TAG);

        foreach (array_keys($taggedServices) as $id) {
            $register = $container->getDefinition($id);
            $register->addMethodCall('register', []);
        }
    }
}
