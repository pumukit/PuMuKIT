<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\AddUserToGroup;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class AddUserToGroupValidator
{
    public static function validate(AddUserToGroupRequest $request): void
    {
        if (empty($request->groupId)) {
            throw new InvalidArgumentException('Group ID is required.');
        }
        if (empty($request->userId)) {
            throw new InvalidArgumentException('User ID is required.');
        }
    }
}
