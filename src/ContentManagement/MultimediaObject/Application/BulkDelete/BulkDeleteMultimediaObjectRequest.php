<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectRequest
{
    public function __construct(
        public array $multimediaObjectIds
    ) {}
}
