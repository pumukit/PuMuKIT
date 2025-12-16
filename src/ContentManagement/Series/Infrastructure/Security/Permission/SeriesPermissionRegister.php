<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class SeriesPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(SeriesPermissions::all(), 'series');
        $this->registry->register(SeriesUIPermissions::all(), 'series');
    }
}
