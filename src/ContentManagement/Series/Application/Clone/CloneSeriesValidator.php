<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Clone;

use App\Shared\Domain\Validator\UuidValidator;

final class CloneSeriesValidator
{
    public static function validate(CloneSeriesRequest $request): void
    {
        UuidValidator::validate($request->seriesId, 'Series ID');
    }
}
