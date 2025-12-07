<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\BulkDelete;

final class BulkDeletePlaylistValidator
{
    public static function validate(BulkDeletePlaylistRequest $request): void
    {
        if (empty($request->playlistIds)) {
            throw new \InvalidArgumentException('At least one playlist ID must be provided');
        }

        foreach ($request->playlistIds as $id) {
            if (!is_string($id) || empty($id)) {
                throw new \InvalidArgumentException('All playlist IDs must be non-empty strings');
            }
        }
    }
}
