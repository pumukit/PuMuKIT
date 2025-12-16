<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Security\Permission;

final class GroupPermissions
{
    public const VIEW = 'group.view';
    public const CREATE = 'group.create';
    public const EDIT = 'group.edit';
    public const DELETE = 'group.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View group',
            self::CREATE => 'Create group',
            self::EDIT => 'Edit group',
            self::DELETE => 'Delete group',
        ];
    }
}
