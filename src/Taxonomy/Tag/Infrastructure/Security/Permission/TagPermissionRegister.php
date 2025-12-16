<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class TagPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(TagPermissions::all(), 'tag');
        $this->registry->register(TagUIPermissions::all(), 'tag', PermissionType::UI);
    }
}
