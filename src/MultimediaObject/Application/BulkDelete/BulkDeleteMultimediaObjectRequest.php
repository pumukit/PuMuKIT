<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectRequest
{
    public function __construct(
        public readonly array $multimediaObjectIds
    ) {}
}

