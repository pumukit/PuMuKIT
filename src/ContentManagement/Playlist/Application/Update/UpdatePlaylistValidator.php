<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Update;

use App\Shared\Domain\Validator\IdValidator;

final class UpdatePlaylistValidator
{
    public static function validate(UpdatePlaylistRequest $request): void
    {
        IdValidator::validate($request->id, 'Playlist ID');
    }
}
