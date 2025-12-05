<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class UserPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(UserPermissions::all(), 'user');
    }
}

