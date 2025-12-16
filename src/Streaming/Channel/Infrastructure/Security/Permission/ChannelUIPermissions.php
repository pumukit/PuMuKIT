<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Security\Permission;

final class ChannelUIPermissions
{
    public const UI_SHOW_MENU_CHANNEL = 'ui.channel.menu.show';

    public static function getMenuPermission(): string
    {
        return self::UI_SHOW_MENU_CHANNEL;
    }

    public static function all(): array
    {
        return [
            self::UI_SHOW_MENU_CHANNEL => 'Show Channel Menu Link in Navigation',
        ];
    }
}
