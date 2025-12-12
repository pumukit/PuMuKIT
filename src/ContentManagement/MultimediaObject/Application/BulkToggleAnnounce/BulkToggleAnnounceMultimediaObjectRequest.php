<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceMultimediaObjectRequest
{
    public function __construct(
        public array $multimediaObjectIds
    ) {}
}
