<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Cancel;

use App\Shared\Domain\Validator\UuidValidator;

final class CancelJobValidator
{
    public static function validate(CancelJobRequest $request): void
    {
        UuidValidator::validate($request->id, 'Job ID');
    }
}

