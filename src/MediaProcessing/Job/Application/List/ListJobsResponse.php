<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\List;

final class ListJobsResponse
{
    public function __construct(
        public iterable $jobs,
        public int $total,
        public int $page,
        public int $limit
    ) {}
}
