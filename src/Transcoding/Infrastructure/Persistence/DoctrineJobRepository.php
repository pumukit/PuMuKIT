<?php

declare(strict_types=1);

namespace App\Transcoding\Infrastructure\Persistence;

use App\Transcoding\Domain\Repository\JobRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

final class DoctrineJobRepository implements JobRepositoryInterface
{
    public function __construct(private readonly DocumentManager $documentManager) {}

    public function find(string $id): ?Job
    {
        return $this->documentManager
            ->getRepository(Job::class)
            ->find($id)
        ;
    }

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null): iterable
    {
        $qb = $this->documentManager
            ->getRepository(Job::class)
            ->createQueryBuilder()
        ;

        if ($sort) {
            $mongoSort = $this->convertSortToMongoFormat($sort);
            $qb->sort($mongoSort);
        }

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
        ;

        return $qb->getQuery()->execute();
    }

    public function countAll(): int
    {
        return $this->documentManager
            ->getRepository(Job::class)
            ->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function save(Job $job): void
    {
        $this->documentManager->persist($job);
        $this->documentManager->flush();
    }

    private function convertSortToMongoFormat(array $sort): array
    {
        $mongoSort = [];
        foreach ($sort as $field => $direction) {
            if (is_string($direction)) {
                $mongoSort[$field] = 'asc' === strtolower($direction) ? 1 : -1;
            } elseif (is_int($direction)) {
                $mongoSort[$field] = $direction >= 0 ? 1 : -1;
            } else {
                $mongoSort[$field] = -1;
            }
        }

        return $mongoSort;
    }
}
