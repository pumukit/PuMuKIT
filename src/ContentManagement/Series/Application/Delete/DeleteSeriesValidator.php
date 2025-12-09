<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Delete;

use App\Shared\Domain\Validator\UuidValidator;

final class DeleteSeriesValidator
{
    public static function validate(DeleteSeriesRequest $request): void
    {
        UuidValidator::validate($request->id, 'Series ID');
    }
}
