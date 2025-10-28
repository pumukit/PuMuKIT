<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Delete;

final class DeleteMultimediaObjectValidator
{
    public static function validate(DeleteMultimediaObjectRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('MultimediaObject ID cannot be empty.');
        }

        // Validate MongoDB ObjectId format (24 hex characters)
        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid MultimediaObject ID format: %s', $request->id)
            );
        }
    }
}

