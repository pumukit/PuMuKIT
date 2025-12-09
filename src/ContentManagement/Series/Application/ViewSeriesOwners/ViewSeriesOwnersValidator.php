<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesOwners;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewSeriesOwnersValidator
{
    public static function validate(ViewSeriesOwnersRequest $request): void
    {
        UuidValidator::validate($request->seriesId, 'Series ID');
    }
}
