<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesOwners;

use App\Shared\Domain\Validator\IdValidator;

final class ViewSeriesOwnersValidator
{
    public static function validate(ViewSeriesOwnersRequest $request): void
    {
        IdValidator::validate($request->seriesId, 'Series ID');
    }
}
