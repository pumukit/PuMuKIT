<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Security\Permission;

final class PlaylistUIPermissions
{
    public const UI_SHOW_MENU_PLAYLIST = 'ui.playlist.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_PLAYLIST;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_PLAYLIST => 'Show Playlist Menu Link in Navigation',
        ];
    }
}
