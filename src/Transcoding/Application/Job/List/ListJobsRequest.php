<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\List;

final class ListJobsRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly string $sort = 'timeini',
        public readonly string $order = 'desc'
    ) {}
}
