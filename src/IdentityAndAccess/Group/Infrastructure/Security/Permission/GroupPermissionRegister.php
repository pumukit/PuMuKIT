<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class GroupPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(GroupPermissions::all(), 'group', PermissionType::DOMAIN);
        $this->registry->register(GroupUIPermissions::all(), 'group', PermissionType::UI);
    }
}
