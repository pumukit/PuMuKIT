<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Security\Permission;


use App\Shared\Domain\PermissionRegistryInterface;

final class RolePermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(RolePermissions::all(), 'role');
        $this->registry->register(RoleUIPermissions::all(), 'role');
    }
}
