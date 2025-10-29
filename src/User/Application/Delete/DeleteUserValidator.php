<?php

declare(strict_types=1);

namespace App\User\Application\Delete;

final class DeleteUserValidator
{
    public static function validate(DeleteUserRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }

        // Validate MongoDB ObjectId format (24 hex characters)
        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid User ID format: %s', $request->id)
            );
        }
    }
}
