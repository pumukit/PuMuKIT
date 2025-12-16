<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class CpuPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(CpuPermissions::all(), 'cpu');
        $this->registry->register(CpuUIPermissions::all(), 'cpu');
    }
}
