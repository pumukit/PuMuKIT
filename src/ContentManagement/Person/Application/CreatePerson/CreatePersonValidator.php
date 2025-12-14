<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\CreatePerson;

final class CreatePersonValidator
{
    public static function validate(CreatePersonRequest $request): void
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException('Person name cannot be empty');
        }

        if ($request->email !== null && !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email format: %s', $request->email)
            );
        }
    }
}

