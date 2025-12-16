<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class UserPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(UserPermissions::all(), 'user', PermissionType::DOMAIN);
        $this->registry->register(UserUIPermissions::all(), 'user', PermissionType::UI);
    }
}
