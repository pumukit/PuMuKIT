<?php

namespace App\Series\Application\Delete;

final class DeleteSeriesResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
