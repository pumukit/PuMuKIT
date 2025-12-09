<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\BulkDelete;

use App\Shared\Domain\Validator\UuidValidator;

final class BulkDeleteSeriesValidator
{
    public static function validate(BulkDeleteSeriesRequest $request): void
    {
        UuidValidator::validateArray($request->seriesIds, 'Series ID');

        $uniqueIds = array_unique($request->seriesIds);
        if (count($uniqueIds) !== count($request->seriesIds)) {
            throw new \InvalidArgumentException('Duplicate series IDs found in request');
        }
    }
}
