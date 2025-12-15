<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Find;

final class FindGroupValidator
{
    public static function validate(FindGroupRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Group ID cannot be empty');
        }
    }
}
