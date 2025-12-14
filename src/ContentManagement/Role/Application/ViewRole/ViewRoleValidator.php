<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\ViewRole;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewRoleValidator
{
    public static function validate(ViewRoleRequest $request): void
    {
        UuidValidator::validate($request->id, 'Role ID');
    }
}

