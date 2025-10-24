<?php

namespace App\Series\Domain\Events;

use App\User\Domain\ValueObject\UserId;

final class SeriesCreatedEvent
{
    private string $seriesId;
    private UserId $ownerId;

    public function __construct(string $seriesId, UserId $ownerId)
    {
        $this->seriesId = $seriesId;
        $this->ownerId = $ownerId;
    }

    public function seriesId(): string
    {
        return $this->seriesId;
    }

    public function ownerId(): UserId
    {
        return $this->ownerId;
    }
}
