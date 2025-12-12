<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Delete;

final class DeleteChannelResponse
{
    public function __construct(public string $deletedId) {}
}
