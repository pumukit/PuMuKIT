<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\BulkDelete;

final class BulkDeleteGroupResponse
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
        if (!empty($this->failedIds)) {
            $data['failed_ids'] = $this->failedIds;
            $data['failed_count'] = count($this->failedIds);
        }
        if (!empty($this->errors)) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }

    private function generateMessage(): string
    {
        if (0 === $this->deletedCount) {
            return 'group.bulk_delete.error.all_failed';
        }
        if (!empty($this->failedIds)) {
            return 'group.bulk_delete.partial_success';
        }

        return 'group.bulk_delete.success';
    }
}
