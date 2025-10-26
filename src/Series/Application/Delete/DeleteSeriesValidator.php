<?php

namespace App\Series\Application\Delete;

use InvalidArgumentException;

final class DeleteSeriesValidator
{
    public static function validate(DeleteSeriesRequest $request): void
    {
        if (empty($request->id)) {
            throw new InvalidArgumentException('Series ID cannot be empty.');
        }

        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            // Por si estás usando MongoDB con ObjectId de 24 chars
            throw new InvalidArgumentException('Invalid Series ID format.');
        }
    }
}
