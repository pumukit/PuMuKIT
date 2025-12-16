<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Security\Permission;

final class PersonPermissions
{
    public const CREATE = 'person.create';
    public const VIEW = 'person.view';
    public const EDIT = 'person.edit';
    public const DELETE = 'person.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View person',
            self::CREATE => 'Create person',
            self::EDIT => 'Edit person',
            self::DELETE => 'Delete person',
        ];
    }
}
