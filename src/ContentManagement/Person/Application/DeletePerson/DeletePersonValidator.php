<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\DeletePerson;

use App\Shared\Domain\Validator\UuidValidator;

final class DeletePersonValidator
{
    public static function validate(DeletePersonRequest $request): void
    {
        UuidValidator::validate($request->id, 'Person ID');
    }
}
