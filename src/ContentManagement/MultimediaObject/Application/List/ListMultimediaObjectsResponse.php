<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\List;

final class ListMultimediaObjectsResponse
{
    public function __construct(
        public iterable $multimediaObjects,
        public int $total,
        public int $page,
        public int $limit
    ) {}
}
