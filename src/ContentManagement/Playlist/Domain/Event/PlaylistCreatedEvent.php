<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Series;

final readonly class PlaylistCreatedEvent extends DomainEvent
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
