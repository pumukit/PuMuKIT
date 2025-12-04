<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Delete;

final class DeleteChannelRequest
{
    public function __construct(public readonly string $id) {}
}
