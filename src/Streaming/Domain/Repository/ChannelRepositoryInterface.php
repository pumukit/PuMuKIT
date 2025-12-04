<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Repository;

use Pumukit\SchemaBundle\Document\Live;

interface ChannelRepositoryInterface
{
    public function find(string $id): ?Live;

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null): iterable;

    public function countAll(): int;

    public function save(Live $channel): void;

    public function delete(Live $channel): void;
}
