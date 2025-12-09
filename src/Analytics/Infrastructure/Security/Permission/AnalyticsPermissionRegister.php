<?php

declare(strict_types=1);

namespace App\Analytics\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class AnalyticsPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(StatsPermissions::all(), 'stats');
    }
}
