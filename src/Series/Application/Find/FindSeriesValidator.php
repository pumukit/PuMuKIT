<?php

declare(strict_types=1);

namespace App\Series\Application\Find;

final class FindSeriesValidator
{
    public static function validate(FindSeriesRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Series ID cannot be empty');
        }

        // Validate MongoDB ObjectId format (24 hex characters)
        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid Series ID format: %s', $request->id)
            );
        }
    }
}

