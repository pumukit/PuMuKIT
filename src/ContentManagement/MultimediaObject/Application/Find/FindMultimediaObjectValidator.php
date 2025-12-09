<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Find;

use App\Shared\Domain\Validator\UuidValidator;

final class FindMultimediaObjectValidator
{
    public static function validate(FindMultimediaObjectRequest $request): void
    {
        UuidValidator::validate($request->id, 'MultimediaObject ID');
    }
}
