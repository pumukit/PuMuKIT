<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Clone;

final class CloneSeriesRequest
{
    public function __construct(
        public string $seriesId
    ) {}
    }
}
