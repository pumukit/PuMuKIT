<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceMultimediaObjectRequest
{
    public function __construct(
        public readonly array $multimediaObjectIds
    ) {}
}
