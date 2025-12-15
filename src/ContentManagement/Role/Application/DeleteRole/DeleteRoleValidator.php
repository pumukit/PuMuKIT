<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\DeleteRole;

use App\Shared\Domain\Validator\UuidValidator;

final class DeleteRoleValidator
{
    public static function validate(DeleteRoleRequest $request): void
    {
        UuidValidator::validate($request->id, 'Role ID');
    }
}
