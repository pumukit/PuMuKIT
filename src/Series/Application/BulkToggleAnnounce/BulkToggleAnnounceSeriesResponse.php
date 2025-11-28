<?php

declare(strict_types=1);

namespace App\Series\Application\BulkToggleAnnounce;

final class BulkToggleAnnounceSeriesResponse
{
    public function __construct(
        public readonly int $updatedCount,
        public readonly int $announcedCount,
        public readonly int $unAnnouncedCount,
        public readonly array $failedIds = [],
        public readonly array $errors = [],
        public readonly string $message = ''
    ) {}

    public function isFullySuccessful(): bool
    {
        return empty($this->failedIds);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function toArray(): array
    {
        $data = [
            'success' => $this->updatedCount > 0,
            'message' => $this->message ?: $this->generateMessage(),
            'updated_count' => $this->updatedCount,
            'announced_count' => $this->announcedCount,
            'un_announced_count' => $this->unAnnouncedCount,
        ];

        if (!$this->isFullySuccessful()) {
            $data['failed_count'] = count($this->failedIds);
            $data['failed_ids'] = $this->failedIds;
            $data['errors'] = $this->formatErrors();
        }

        return $data;
    }

    private function generateMessage(): string
    {
        if ($this->isFullySuccessful()) {
            return sprintf(
                'Successfully toggled announce for %d series (%d announced, %d not announced)',
                $this->updatedCount,
                $this->announcedCount,
                $this->unAnnouncedCount
            );
        }

        return sprintf(
            'Toggled announce for %d series (%d announced, %d not announced). Failed for %d series.',
            $this->updatedCount,
            $this->announcedCount,
            $this->unAnnouncedCount,
            count($this->failedIds)
        );
    }

    private function formatErrors(): array
    {
        $errorMessages = [];
        foreach ($this->errors as $id => $error) {
            $errorMessages[] = sprintf('ID %s: %s', $id, $error);
        }

        return $errorMessages;
    }
}
