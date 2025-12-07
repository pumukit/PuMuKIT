<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Delete;

final class DeleteMultimediaObjectRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
