<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce;

use App\Shared\Domain\Validator\UuidValidator;

final class BulkToggleAnnounceMultimediaObjectValidator
{
    public static function validate(BulkToggleAnnounceMultimediaObjectRequest $request): void
    {
        UuidValidator::validateArray($request->multimediaObjectIds, 'Multimedia object ID');

        $uniqueIds = array_unique($request->multimediaObjectIds);
        if (count($uniqueIds) !== count($request->multimediaObjectIds)) {
            throw new \InvalidArgumentException('Duplicate multimedia object IDs found in request');
        }
    }
}
