<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\CreateRole;

final class CreateRoleValidator
{
    public static function validate(CreateRoleRequest $request): void
    {
        if (empty($request->cod)) {
            throw new \InvalidArgumentException('Role code cannot be empty');
        }

        if (empty($request->name)) {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }
    }
}
