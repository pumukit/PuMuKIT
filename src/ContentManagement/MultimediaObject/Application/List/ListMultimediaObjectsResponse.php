<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\List;

final class ListMultimediaObjectsResponse
{
    public function __construct(
        public readonly iterable $multimediaObjects,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit
    ) {}
}
