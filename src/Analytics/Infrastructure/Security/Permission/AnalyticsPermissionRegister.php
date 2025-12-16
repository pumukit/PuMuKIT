<?php

declare(strict_types=1);

namespace App\Analytics\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class AnalyticsPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(StatsUIPermissions::all(), 'stats', PermissionType::UI);
    }
}
