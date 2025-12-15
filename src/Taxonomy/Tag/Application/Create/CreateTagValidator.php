<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Create;

use App\Shared\Domain\Validator\UuidValidator;

final class CreateTagValidator
{
    public static function validate(CreateTagRequest $request): void
    {
        if (empty($request->cod)) {
            throw new \InvalidArgumentException('Tag code cannot be empty');
        }

        if (empty($request->title)) {
            throw new \InvalidArgumentException('Tag title cannot be empty');
        }

        if ($request->parentId !== null) {
            UuidValidator::validate($request->parentId, 'Parent ID');
        }
    }
}

