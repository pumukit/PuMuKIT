<?php

declare(strict_types=1);

namespace App\MultimediaObject\Infrastructure\Security\Permission;

final class MultimediaObjectPermissions
{
    public const CREATE = 'multimedia_object.create';
    public const VIEW   = 'multimedia_object.view';
    public const EDIT   = 'multimedia_object.edit';
    public const DELETE = 'multimedia_object.delete';

    public static function all(): array
    {
        return [
            self::VIEW   => 'View multimedia object',
            self::CREATE => 'Create multimedia object',
            self::EDIT   => 'Edit multimedia object',
            self::DELETE => 'Delete multimedia object',
        ];
    }
}

