<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Delete;

use App\Shared\Domain\Validator\UuidValidator;

final class DeleteTagValidator
{
    public static function validate(DeleteTagRequest $request): void
    {
        UuidValidator::validate($request->id, 'Tag ID');
    }
}
