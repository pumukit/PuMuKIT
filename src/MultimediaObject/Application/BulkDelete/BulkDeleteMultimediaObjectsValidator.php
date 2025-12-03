<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

use InvalidArgumentException;

final class BulkDeleteMultimediaObjectsValidator
{
    public static function validate(BulkDeleteMultimediaObjectsRequest $request): void
    {
        if (empty($request->multimediaObjectIds)) {
            throw new InvalidArgumentException('At least one multimedia object ID must be provided');
        }

        if (!is_array($request->multimediaObjectIds)) {
            throw new InvalidArgumentException('Multimedia object IDs must be an array');
        }

        foreach ($request->multimediaObjectIds as $id) {
            if (!is_string($id) || empty($id)) {
                throw new InvalidArgumentException('Each multimedia object ID must be a non-empty string');
            }

            if (!preg_match('/^[a-f0-9]{24}$/i', $id)) {
                throw new InvalidArgumentException(sprintf('Invalid MongoDB ObjectId format: %s', $id));
            }
        }
    }
}

