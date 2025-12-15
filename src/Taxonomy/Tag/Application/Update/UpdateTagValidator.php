<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Update;

use App\Shared\Domain\Validator\UuidValidator;

final class UpdateTagValidator
{
    public static function validate(UpdateTagRequest $request): void
    {
        UuidValidator::validate($request->id, 'Tag ID');

        if (empty($request->title)) {
            throw new \InvalidArgumentException('Tag title cannot be empty');
        }
    }
}
