<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\List;

final class ListPlaylistValidator
{
    public static function validate(ListPlaylistRequest $request): void
    {
        if ($request->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if ($request->limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than 0');
        }

        if ($request->limit > 100) {
            throw new \InvalidArgumentException('Limit cannot exceed 100');
        }
    }
}
