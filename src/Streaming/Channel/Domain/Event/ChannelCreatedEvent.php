<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Live;

final readonly class ChannelCreatedEvent extends DomainEvent
{
    public function __construct(
        public Live $channel
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'channel.created';
    }
}
