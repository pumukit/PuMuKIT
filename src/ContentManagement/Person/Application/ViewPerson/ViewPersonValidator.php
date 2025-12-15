<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewPersonValidator
{
    public static function validate(ViewPersonRequest $request): void
    {
        UuidValidator::validate($request->id, 'Person ID');
    }
}
