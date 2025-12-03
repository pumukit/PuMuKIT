<?php

declare(strict_types=1);

namespace App\Transcoding\Domain\Repository;

use Pumukit\EncoderBundle\Document\Job;

interface JobRepositoryInterface
{
    public function find(string $id): ?Job;

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null): iterable;

    public function countAll(): int;

    public function save(Job $job): void;
}

