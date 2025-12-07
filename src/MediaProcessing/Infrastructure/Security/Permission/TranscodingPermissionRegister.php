<?php

declare(strict_types=1);

namespace App\MediaProcessing\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class TranscodingPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(CpuPermissions::all(), 'cpu');
        $this->registry->register(JobPermissions::all(), 'job');
    }
}

