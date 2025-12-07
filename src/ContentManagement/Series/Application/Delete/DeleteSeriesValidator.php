<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Delete;

use App\Shared\Domain\Validator\IdValidator;

final class DeleteSeriesValidator
{
    public static function validate(DeleteSeriesRequest $request): void
    {
        IdValidator::validate($request->id, 'Series ID');
    }
}
