<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class MultimediaObjectPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(MultimediaObjectPermissions::all(), 'multimedia_object');
        $this->registry->register(MultimediaObjectUIPermissions::all(), 'multimedia_object', PermissionType::UI);
    }
}
