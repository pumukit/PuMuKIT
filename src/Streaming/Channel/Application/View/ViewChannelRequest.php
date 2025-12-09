<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\View;

final class ViewChannelRequest
{
    public function __construct(public readonly string $id) {}
}
