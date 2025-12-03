<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use RuntimeException;

final class ChannelNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Channel with ID "%s" not found', $id));
    }
}

