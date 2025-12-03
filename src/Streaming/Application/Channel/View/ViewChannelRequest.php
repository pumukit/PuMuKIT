<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\View;

final class ViewChannelRequest
{
    public function __construct(public readonly string $id) {}
}


