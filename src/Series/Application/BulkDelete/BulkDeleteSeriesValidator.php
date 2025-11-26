<?php

declare(strict_types=1);

namespace App\Series\Application\BulkDelete;

final class BulkDeleteSeriesValidator
{
    public static function validate(BulkDeleteSeriesRequest $request): void
    {
        if (empty($request->seriesIds)) {
            throw new \InvalidArgumentException('Series IDs array cannot be empty');
        }

        foreach ($request->seriesIds as $seriesId) {
            if (empty($seriesId) || !is_string($seriesId)) {
                throw new \InvalidArgumentException('All series IDs must be non-empty strings');
            }

            if (!preg_match('/^[a-f0-9]{24}$/', $seriesId)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid series ID format: %s', $seriesId)
                );
            }
        }

        $uniqueIds = array_unique($request->seriesIds);
        if (count($uniqueIds) !== count($request->seriesIds)) {
            throw new \InvalidArgumentException('Duplicate series IDs found in request');
        }
    }
}

