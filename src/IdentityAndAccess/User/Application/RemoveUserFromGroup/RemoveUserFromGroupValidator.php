<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\RemoveUserFromGroup;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class RemoveUserFromGroupValidator
{
    public static function validate(RemoveUserFromGroupRequest $request): void
    {
        if (empty($request->groupId)) {
            throw new InvalidArgumentException('Group ID is required to remove a user.');
        }
        if (empty($request->userId)) {
            throw new InvalidArgumentException('User ID is required to remove a user.');
        }
    }
}
