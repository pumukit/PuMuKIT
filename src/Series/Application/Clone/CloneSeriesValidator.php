<?php

declare(strict_types=1);

namespace App\Series\Application\Clone;

final class CloneSeriesValidator
{
    public static function validate(CloneSeriesRequest $request): void
    {
        if (empty($request->seriesId)) {
            throw new \InvalidArgumentException('Series ID cannot be empty');
        }

        // Validate MongoDB ObjectId format (24 hex characters)
        if (!preg_match('/^[a-f0-9]{24}$/i', $request->seriesId)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid Series ID format: %s', $request->seriesId)
            );
        }
    }
}
