<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Find;

use App\Shared\Domain\Validator\IdValidator;

final class FindSeriesValidator
{
    public static function validate(FindSeriesRequest $request): void
    {
        IdValidator::validate($request->id, 'Series ID');
    }
}
