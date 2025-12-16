<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class JobPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(JobPermissions::all(), 'job');
        $this->registry->register(JobUIPermissions::all(), 'job', PermissionType::UI);
    }
}
