<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Delete;

use App\Shared\Domain\Validator\IdValidator;

final class DeletePlaylistValidator
{
    public static function validate(DeletePlaylistRequest $request): void
    {
        IdValidator::validate($request->id, 'Playlist ID');
    }
}
