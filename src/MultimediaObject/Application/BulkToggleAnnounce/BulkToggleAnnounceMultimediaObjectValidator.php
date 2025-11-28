<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkToggleAnnounce;

use App\Shared\Domain\Validator\IdValidator;

final class BulkToggleAnnounceMultimediaObjectValidator
{
    public static function validate(BulkToggleAnnounceMultimediaObjectRequest $request): void
    {
        IdValidator::validateArray($request->multimediaObjectIds, 'Multimedia object ID');

        $uniqueIds = array_unique($request->multimediaObjectIds);
        if (count($uniqueIds) !== count($request->multimediaObjectIds)) {
            throw new \InvalidArgumentException('Duplicate multimedia object IDs found in request');
        }
    }
}

