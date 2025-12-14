<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\UpdateRole;

use App\Shared\Domain\Validator\UuidValidator;

final class UpdateRoleValidator
{
    public static function validate(UpdateRoleRequest $request): void
    {
        UuidValidator::validate($request->id, 'Role ID');

        if (empty($request->name)) {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }
    }
}

