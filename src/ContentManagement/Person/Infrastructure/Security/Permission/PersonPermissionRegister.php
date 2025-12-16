<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Security\Permission;

use App\ContentManagement\MultimediaObject\Infrastructure\Security\Permission\PersonPermissions;
use App\ContentManagement\MultimediaObject\Infrastructure\Security\Permission\PersonUIPermissions;
use App\Shared\Domain\PermissionRegistryInterface;

final class PersonPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(PersonPermissions::all(), 'person');
        $this->registry->register(PersonUIPermissions::all(), 'person');
    }
}
