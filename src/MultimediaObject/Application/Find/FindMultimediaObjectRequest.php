<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\Find;

final class FindMultimediaObjectRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
