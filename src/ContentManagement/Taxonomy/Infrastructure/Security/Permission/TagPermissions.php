<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Infrastructure\Security\Permission;

final class TagPermissions
{
    public const CREATE = 'tag.create';
    public const VIEW = 'tag.view';
    public const EDIT = 'tag.edit';
    public const DELETE = 'tag.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View tags',
            self::CREATE => 'Create tags',
            self::EDIT => 'Edit tags',
            self::DELETE => 'Delete tags',
        ];
    }
}
