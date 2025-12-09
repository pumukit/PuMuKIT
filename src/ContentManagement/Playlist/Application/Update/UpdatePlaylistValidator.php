<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Update;

use App\Shared\Domain\Validator\UuidValidator;

final class UpdatePlaylistValidator
{
    public static function validate(UpdatePlaylistRequest $request): void
    {
        UuidValidator::validate($request->id, 'Playlist ID');
    }
}
