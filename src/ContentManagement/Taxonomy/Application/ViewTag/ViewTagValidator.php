<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ViewTag;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewTagValidator
{
    public static function validate(ViewTagRequest $request): void
    {
        UuidValidator::validate($request->id, 'Tag ID');
    }
}

