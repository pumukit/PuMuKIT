<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class DashboardPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(DashboardPermissions::all(), 'dashboard');
    }
}

