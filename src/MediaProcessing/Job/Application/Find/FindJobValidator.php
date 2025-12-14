<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Find;

use App\Shared\Domain\Validator\UuidValidator;

final class FindJobValidator
{
    public static function validate(FindJobRequest $request): void
    {
        UuidValidator::validate($request->id, 'Job ID');
    }
}

