<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class ChannelDeletedEvent extends DomainEvent
{
    public function __construct(
        public string $channelId
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'channel.deleted';
    }
}
