<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceMultimediaObjectResponse
{
    public function __construct(
        public int $updatedCount,
        public int $announcedCount,
        public int $unAnnouncedCount,
        public array $failedIds = [],
        public array $errors = []
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
