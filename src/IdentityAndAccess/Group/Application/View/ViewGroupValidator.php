<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\View;

final class ViewGroupValidator
{
    public static function validate(ViewGroupRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Group ID cannot be empty');
        }

        if (empty($request->tab)) {
            throw new \InvalidArgumentException('Tab cannot be empty');
        }
    }
}
