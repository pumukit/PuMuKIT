<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

use App\Shared\Domain\Validator\IdValidator;

final class CreatePlaylistValidator
{
    public static function validate(CreatePlaylistRequest $request): void
    {
        IdValidator::validate($request->ownerId, 'Owner ID');
    }
}
