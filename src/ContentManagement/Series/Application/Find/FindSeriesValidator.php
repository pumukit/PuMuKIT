<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Find;

use App\Shared\Domain\Validator\UuidValidator;

final class FindSeriesValidator
{
    public static function validate(FindSeriesRequest $request): void
    {
        UuidValidator::validate($request->id, 'Series ID');
    }
}
