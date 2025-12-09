<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Security\Permission;

final class SeriesPermissions
{
    public const VIEW = 'series.view';
    public const CREATE = 'series.create';
    public const EDIT = 'series.edit';
    public const DELETE = 'series.delete';

    public static function all(): array
    {
        return [
            self::VIEW => 'View series',
            self::CREATE => 'Create series',
            self::EDIT => 'Edit series',
            self::DELETE => 'Delete series',
        ];
    }
}
