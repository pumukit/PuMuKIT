<?php

declare(strict_types=1);

namespace App\Series\Application\Create;

final class CreateSeriesValidator
{
    public static function validate(CreateSeriesRequest $request): void
    {
        if (empty($request->ownerId)) {
            throw new \InvalidArgumentException('Owner ID cannot be empty');
        }

        if (!preg_match('/^[a-f0-9]{24}$/i', $request->ownerId)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid Owner ID format: %s', $request->ownerId)
            );
        }

        if (null !== $request->title) {
            if (!is_array($request->title)) {
                throw new \InvalidArgumentException('Title must be an array');
            }

            if (empty($request->title)) {
                throw new \InvalidArgumentException('Title array cannot be empty');
            }
        }
    }
}
