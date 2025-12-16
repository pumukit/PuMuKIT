<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Security\Permission;

final class RolePermissions
{
    public const CREATE = 'role.create';
    public const VIEW = 'role.view';
    public const EDIT = 'role.edit';
    public const DELETE = 'role.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View role',
            self::CREATE => 'Create role',
            self::EDIT => 'Edit role',
            self::DELETE => 'Delete role',
        ];
    }
}
