<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Security\Permission;

final class ChannelPermissions
{
    public const VIEW   = 'channel.view';
    public const CREATE   = 'channel.create';
    public const EDIT   = 'channel.edit';
    public const DELETE   = 'channel.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View channels',
            self::CREATE => 'Create channels',
            self::EDIT => 'Edit channels',
            self::DELETE => 'Delete channels',
        ];
    }
}


