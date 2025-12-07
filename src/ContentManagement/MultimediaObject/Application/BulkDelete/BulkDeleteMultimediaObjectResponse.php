<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectResponse
{
    public function __construct(
        public readonly int $deletedCount,
        public readonly array $failedIds = [],
        public readonly array $errors = []
    ) {}

    public function isFullySuccessful(): bool
    {
        return empty($this->failedIds);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
