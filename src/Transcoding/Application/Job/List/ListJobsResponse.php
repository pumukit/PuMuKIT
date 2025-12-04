<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\List;

final class ListJobsResponse
{
    public function __construct(
        public readonly iterable $jobs,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit
    ) {}
}
