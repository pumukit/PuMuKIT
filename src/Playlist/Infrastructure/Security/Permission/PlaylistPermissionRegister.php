<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class PlaylistPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(PlaylistPermissions::all(), 'playlist', PermissionType::DOMAIN);
        $this->registry->register(PlaylistUIPermissions::all(), 'playlist', PermissionType::UI);
    }
}
