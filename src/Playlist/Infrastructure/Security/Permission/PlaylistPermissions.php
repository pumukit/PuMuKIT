<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Security\Permission;

final class PlaylistPermissions
{
    public const CREATE = 'playlist.create';
    public const VIEW   = 'playlist.view';
    public const EDIT   = 'playlist.edit';
    public const DELETE = 'playlist.delete';

    public static function all(): array
    {
        return [
            self::VIEW   => 'View playlist',
            self::CREATE => 'Create playlist',
            self::EDIT   => 'Edit playlist',
            self::DELETE => 'Delete playlist',
        ];
    }
}

