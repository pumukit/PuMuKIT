<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Delete;

final class DeleteChannelRequest
{
    public function __construct(public string $id) {}
}
