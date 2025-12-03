<?php

namespace App\Playlist\Domain\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistCreatedEvent
{
    public const NAME = 'playlist.created';

    public function __construct(
        private Series $playlist
    ) {}

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }
}
