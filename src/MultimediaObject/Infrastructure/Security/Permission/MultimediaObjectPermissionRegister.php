<?php

declare(strict_types=1);

namespace App\MultimediaObject\Infrastructure\Security\Permission;

use App\Shared\Infrastructure\Security\Permission\PermissionRegistryInterface;

final class MultimediaObjectPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(MultimediaObjectPermissions::all(), 'multimedia_object');
    }
}

