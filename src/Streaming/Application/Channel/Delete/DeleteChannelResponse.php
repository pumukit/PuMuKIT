<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Delete;

final class DeleteChannelResponse
{
    public function __construct(public readonly string $deletedId) {}
}

