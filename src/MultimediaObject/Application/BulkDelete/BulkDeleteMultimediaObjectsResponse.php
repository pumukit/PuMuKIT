<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\BulkDelete;

final class BulkDeleteMultimediaObjectsResponse
{
    public function __construct(
        public readonly int $deletedCount,
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
            'success' => $this->deletedCount > 0,
            'message' => $this->message ?: $this->generateMessage(),
            'deleted_count' => $this->deletedCount,
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
                'Successfully deleted %d multimedia object(s)',
                $this->deletedCount
            );
        }

        return sprintf(
            'Deleted %d multimedia object(s) of %d. %d failed',
            $this->deletedCount,
            $this->deletedCount + count($this->failedIds),
            count($this->failedIds)
        );
    }

    private function formatErrors(): array
    {
        $formatted = [];
        foreach ($this->errors as $id => $error) {
            $formatted[] = sprintf('MultimediaObject %s: %s', $id, $error);
        }

        return $formatted;
    }
}

