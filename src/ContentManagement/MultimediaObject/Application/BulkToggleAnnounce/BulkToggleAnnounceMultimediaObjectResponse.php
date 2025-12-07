<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceMultimediaObjectResponse
{
    public function __construct(
        public readonly int $updatedCount,
        public readonly int $announcedCount,
        public readonly int $unAnnouncedCount,
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
