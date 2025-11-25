<?php

namespace App\Series\Application\Delete;

final class DeleteSeriesValidator
{
    public static function validate(DeleteSeriesRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('Series ID cannot be empty.');
        }

        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            throw new \InvalidArgumentException('Invalid Series ID format.');
        }
    }
}
