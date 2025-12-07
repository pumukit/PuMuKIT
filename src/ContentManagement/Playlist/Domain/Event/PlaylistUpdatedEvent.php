<?php

namespace App\ContentManagement\Playlist\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistUpdatedEvent
{
    public const NAME = 'playlist.updated';

    public function __construct(
        private Series $playlist
    ) {}

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }
}
