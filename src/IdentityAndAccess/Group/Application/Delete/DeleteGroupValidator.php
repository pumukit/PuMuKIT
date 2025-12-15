<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Delete;

final class DeleteGroupValidator
{
    public static function validate(DeleteGroupRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Group ID cannot be empty');
        }
    }
}
