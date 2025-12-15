<?php

declare(strict_types=1);

namespace App\Playlist\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Series;

final class PlaylistCreatedEvent extends DomainEvent
{
    public function __construct(
        public Series $playlist
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'playlist.created';
    }
}
