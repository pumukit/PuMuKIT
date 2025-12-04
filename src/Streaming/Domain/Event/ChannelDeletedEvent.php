<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Event;

final class ChannelDeletedEvent
{
    public const NAME = 'channel.deleted';

    public function __construct(private string $channelId) {}

    public function getChannelId(): string
    {
        return $this->channelId;
    }
}
