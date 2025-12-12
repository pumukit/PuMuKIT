<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\Find;

final class FindMultimediaObjectRequest
{
    public function __construct(
        public string $id
    ) {}
}
