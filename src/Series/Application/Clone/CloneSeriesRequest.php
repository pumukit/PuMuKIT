<?php

declare(strict_types=1);

namespace App\Series\Application\Clone;

final class CloneSeriesRequest
{
    public function __construct(
        public readonly string $seriesId
    ) {}
}

