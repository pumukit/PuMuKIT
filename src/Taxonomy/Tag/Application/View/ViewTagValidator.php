<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\View;

use App\Shared\Domain\Validator\UuidValidator;

final class ViewTagValidator
{
    public static function validate(ViewTagRequest $request): void
    {
        UuidValidator::validate($request->id, 'Tag ID');
    }
}

