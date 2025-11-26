<?php

declare(strict_types=1);

namespace App\Series\Application\BulkDelete;

final class BulkDeleteSeriesRequest
{
    public function __construct(
        public readonly array $seriesIds
    ) {}
}

