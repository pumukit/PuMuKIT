<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Security\Permission;

use App\MediaProcessing\Cpu\Infrastructure\Security\Permission\CpuPermissions;
use App\Shared\Domain\PermissionRegistryInterface;

final class JobPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(JobPermissions::all(), 'job');
    }
}

