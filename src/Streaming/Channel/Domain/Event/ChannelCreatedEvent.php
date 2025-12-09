<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Domain\Event;

use Pumukit\SchemaBundle\Document\Live;

final class ChannelCreatedEvent
{
    public const NAME = 'channel.created';

    public function __construct(private Live $channel) {}

    public function getChannel(): Live
    {
        return $this->channel;
    }
}
