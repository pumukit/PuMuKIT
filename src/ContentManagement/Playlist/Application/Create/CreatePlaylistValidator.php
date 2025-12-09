<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

use App\Shared\Domain\Validator\UuidValidator;

final class CreatePlaylistValidator
{
    public static function validate(CreatePlaylistRequest $request): void
    {
        UuidValidator::validate($request->ownerId, 'Owner ID');
    }
}
