<?php

declare(strict_types=1);

namespace App\Series\Application\BulkToggleAnnounce;

use App\Shared\Domain\Validator\IdValidator;

final class BulkToggleAnnounceSeriesValidator
{
    public static function validate(BulkToggleAnnounceSeriesRequest $request): void
    {
        IdValidator::validateArray($request->seriesIds, 'Series ID');

        $uniqueIds = array_unique($request->seriesIds);
        if (count($uniqueIds) !== count($request->seriesIds)) {
            throw new \InvalidArgumentException('Duplicate series IDs found in request');
        }
    }
}
