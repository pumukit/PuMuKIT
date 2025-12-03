<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Persistence;

use App\Streaming\Domain\Repository\ChannelRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Live;

final class DoctrineChannelRepository implements ChannelRepositoryInterface
{
    public function __construct(private readonly DocumentManager $documentManager) {}

    public function find(string $id): ?Live
    {
        return $this->documentManager
            ->getRepository(Live::class)
            ->find($id);
    }

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null): iterable
    {
        $qb = $this->documentManager
            ->getRepository(Live::class)
            ->createQueryBuilder();

        if ($sort) {
            $mongoSort = $this->convertSortToMongoFormat($sort);
            $qb->sort($mongoSort);
        }

        $qb->skip(($page - 1) * $limit)
            ->limit($limit);

        return $qb->getQuery()->execute();
    }

    public function countAll(): int
    {
        return $this->documentManager
            ->getRepository(Live::class)
            ->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
    }

    public function save(Live $channel): void
    {
        $this->documentManager->persist($channel);
        $this->documentManager->flush();
    }

    public function delete(Live $channel): void
    {
        $this->documentManager->remove($channel);
        $this->documentManager->flush();
    }

    private function convertSortToMongoFormat(array $sort): array
    {
        $mongoSort = [];
        foreach ($sort as $field => $direction) {
            if (is_string($direction)) {
                $mongoSort[$field] = strtolower($direction) === 'asc' ? 1 : -1;
            } elseif (is_int($direction)) {
                $mongoSort[$field] = $direction >= 0 ? 1 : -1;
            } else {
                $mongoSort[$field] = -1;
            }
        }
        return $mongoSort;
    }
}

