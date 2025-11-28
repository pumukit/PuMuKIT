<?php

namespace App\Series\Application\Delete;

use App\Shared\Domain\Validator\IdValidator;

final class DeleteSeriesValidator
{
    public static function validate(DeleteSeriesRequest $request): void
    {
        IdValidator::validate($request->id, 'Series ID');
    }
}
