<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectsRequest
{
    public function __construct(
        public readonly array $multimediaObjectIds
    ) {
        BulkDeleteMultimediaObjectsValidator::validate($this);
    }
}

