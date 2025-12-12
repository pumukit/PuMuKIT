<?php

namespace App\ContentManagement\Series\Application\View;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewSeriesValidator
{
    public static function validate(ViewSeriesRequest $request): void
    {
        UuidValidator::validate($request->id, 'Series ID');
    }
}
