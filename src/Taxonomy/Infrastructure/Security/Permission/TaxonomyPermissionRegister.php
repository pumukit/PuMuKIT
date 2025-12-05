<?php

declare(strict_types=1);

namespace App\Taxonomy\Infrastructure\Security\Permission;

use App\Shared\Infrastructure\Security\Permission\PermissionRegistryInterface;

final class TaxonomyPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(TagPermissions::all(), 'tag');
    }
}

