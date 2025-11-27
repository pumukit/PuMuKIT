<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectValidator
{
    public static function validate(BulkDeleteMultimediaObjectRequest $request): void
    {
        if (empty($request->multimediaObjectIds)) {
            throw new \InvalidArgumentException('Multimedia object IDs array cannot be empty');
        }

        foreach ($request->multimediaObjectIds as $id) {
            if (empty($id) || !is_string($id)) {
                throw new \InvalidArgumentException('All multimedia object IDs must be non-empty strings');
            }

            if (!preg_match('/^[a-f0-9]{24}$/', $id)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid multimedia object ID format: %s', $id)
                );
            }
        }

        $uniqueIds = array_unique($request->multimediaObjectIds);
        if (count($uniqueIds) !== count($request->multimediaObjectIds)) {
            throw new \InvalidArgumentException('Duplicate multimedia object IDs found in request');
        }
    }
}

