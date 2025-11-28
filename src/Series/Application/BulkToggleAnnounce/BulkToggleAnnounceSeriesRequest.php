<?php

declare(strict_types=1);

namespace App\Series\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceSeriesRequest
{
    public function __construct(
        public readonly array $seriesIds
    ) {
        BulkToggleAnnounceSeriesValidator::validate($this);
    }
}
