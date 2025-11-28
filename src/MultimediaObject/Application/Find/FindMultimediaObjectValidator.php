<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Find;

use App\Shared\Domain\Validator\IdValidator;

final class FindMultimediaObjectValidator
{
    public static function validate(FindMultimediaObjectRequest $request): void
    {
        IdValidator::validate($request->id, 'MultimediaObject ID');
    }
}
