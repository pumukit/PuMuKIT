<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkDelete;

use App\Shared\Domain\Validator\UuidValidator;

final class BulkDeleteMultimediaObjectValidator
{
    public static function validate(BulkDeleteMultimediaObjectRequest $request): void
    {
        UuidValidator::validateArray($request->multimediaObjectIds, 'Multimedia object ID');

        $uniqueIds = array_unique($request->multimediaObjectIds);
        if (count($uniqueIds) !== count($request->multimediaObjectIds)) {
            throw new \InvalidArgumentException('Duplicate multimedia object IDs found in request');
        }
    }
}
