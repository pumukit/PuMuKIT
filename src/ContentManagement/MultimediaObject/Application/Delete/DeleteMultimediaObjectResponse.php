<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Delete;

final class DeleteMultimediaObjectResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $series
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
