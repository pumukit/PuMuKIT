<?php

namespace App\ContentManagement\Series\Application\Delete;

final class DeleteSeriesResponse
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
