<?php

declare(strict_types=1);

namespace App\Playlist\Application\Delete;

use App\Shared\Domain\Validator\UuidValidator;

final class DeletePlaylistValidator
{
    public static function validate(DeletePlaylistRequest $request): void
    {
        UuidValidator::validate($request->id, 'Playlist ID');
    }
}
