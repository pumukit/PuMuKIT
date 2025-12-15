<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\List;

use App\Shared\Domain\Validator\UuidValidator;

final class ListTagsValidator
{
    public static function validate(ListTagsRequest $request): void
    {
        if ($request->parentId !== null) {
            UuidValidator::validate($request->parentId, 'Parent ID');
        }
    }
}

