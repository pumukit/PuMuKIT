<?php

namespace App\ContentManagement\Playlist\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistDeletedEvent
{
    public const NAME = 'playlist.deleted';

    public function __construct(
        private Series $playlist
    ) {}

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }
}
