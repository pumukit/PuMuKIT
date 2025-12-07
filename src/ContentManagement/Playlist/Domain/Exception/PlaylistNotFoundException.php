<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Domain\Exception;

final class PlaylistNotFoundException extends \Exception
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Playlist with id "%s" not found', $id));
    }
}
