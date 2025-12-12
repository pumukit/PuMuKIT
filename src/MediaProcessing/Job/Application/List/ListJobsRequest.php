<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\List;

final class ListJobsRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public string $sort = 'timeini',
        public string $order = 'desc'
    ) {}
}
