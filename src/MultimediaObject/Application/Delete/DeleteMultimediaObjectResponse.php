<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Delete;

final class DeleteMultimediaObjectResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $series
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'series' => $this->series,
        ];
    }
}

