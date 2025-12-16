<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class GroupPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(GroupPermissions::all(), 'group');
        $this->registry->register(GroupUIPermissions::all(), 'group');
    }
}
