<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Security\Permission;

final class UserPermissions
{
    public const CREATE = 'user.create';
    public const VIEW   = 'user.view';
    public const EDIT   = 'user.edit';
    public const DELETE = 'user.delete';

    public static function all(): array
    {
        return [
            self::VIEW   => 'View users',
            self::CREATE => 'Create users',
            self::EDIT   => 'Edit users',
            self::DELETE => 'Delete users',
        ];
    }
}

