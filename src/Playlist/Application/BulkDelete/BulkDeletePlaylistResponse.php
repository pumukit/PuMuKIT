<?php

declare(strict_types=1);

namespace App\Playlist\Application\BulkDelete;

final class BulkDeletePlaylistResponse
{
    public function __construct(
        public readonly int $deletedCount,
        public readonly array $failedIds,
        public readonly array $errors
    ) {}

    public function isFullySuccessful(): bool
    {
        return empty($this->failedIds);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->isFullySuccessful(),
            'deletedCount' => $this->deletedCount,
            'failedCount' => count($this->failedIds),
            'failedIds' => $this->failedIds,
            'errors' => $this->errors,
            'message' => $this->generateMessage(),
        ];
    }

    private function generateMessage(): string
    {
        if ($this->isFullySuccessful()) {
            return sprintf('%d playlist(s) deleted successfully', $this->deletedCount);
        }

        if (0 === $this->deletedCount) {
            return 'Failed to delete all playlists';
        }

        return sprintf(
            '%d playlist(s) deleted successfully, %d failed',
            $this->deletedCount,
            count($this->failedIds)
        );
    }
}
