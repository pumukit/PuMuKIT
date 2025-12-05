<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Security\Permission;

use App\Shared\Infrastructure\Security\Permission\PermissionRegistryInterface;

final class PlaylistPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {
    }

    public function register(): void
    {
        $this->registry->register(PlaylistPermissions::all(), 'playlist');
    }
}

