<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Delete;

use App\Shared\Domain\Validator\IdValidator;

final class DeleteMultimediaObjectValidator
{
    public static function validate(DeleteMultimediaObjectRequest $request): void
    {
        IdValidator::validate($request->id, 'MultimediaObject ID');
    }
}
