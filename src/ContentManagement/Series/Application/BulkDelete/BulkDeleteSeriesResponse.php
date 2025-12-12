<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\BulkDelete;

final class BulkDeleteSeriesResponse
{
    public function __construct(
        public int $deletedCount,
        public array $failedIds = [],
        public array $errors = [],
        public string $message = ''
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
                'Successfully deleted %d series',
                $this->deletedCount
            );
        }

        return sprintf(
            'Deleted %d series. Failed to delete %d series.',
            $this->deletedCount,
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
