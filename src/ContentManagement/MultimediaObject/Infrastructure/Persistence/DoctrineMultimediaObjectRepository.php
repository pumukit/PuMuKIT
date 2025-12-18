<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Persistence;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Search\Infrastructure\Persistence\MongoDb\MongoDbCriteriaConverter;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class DoctrineMultimediaObjectRepository implements MultimediaObjectRepositoryInterface
{
    private $repository;

    public function __construct(
        private DoctrineObjectManager $objectManager,
        private MongoDbCriteriaConverter $criteriaConverter
    ) {
        $this->repository = $this->objectManager->getDocumentManager()->getRepository(MultimediaObject::class);
    }

    public function find(string $id): ?MultimediaObject
    {
        return $this->repository->find($id);
    }

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null, ?array $filters = []): iterable
    {
        $qb = $this->repository->createQueryBuilder();

        $qb->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE);
        $qb->field('type')->notEqual(MultimediaObject::TYPE_LIVE);

        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ('series' === $field) {
                    $qb->field('series')->equals($value);
                } elseif ('status' === $field) {
                    $qb->field('status')->equals((int) $value);
                } elseif ('search' === $field) {
                    $qb->addOr($qb->expr()->field('title.en')->equals(new Regex($value, 'i')));
                    $qb->addOr($qb->expr()->field('title.es')->equals(new Regex($value, 'i')));
                }
            }
        }

        if ($sort) {
            $mongoSort = $this->convertSortToMongoFormat($sort);
            $qb->sort($mongoSort);
        }

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
        ;

        return $qb->getQuery()->execute();
    }

    public function countAll(?array $filters = []): int
    {
        $qb = $this->repository->createQueryBuilder();

        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ('series' === $field) {
                    $qb->field('series')->equals($value);
                } elseif ('status' === $field) {
                    $qb->field('status')->equals((int) $value);
                } elseif ('search' === $field) {
                    $qb->addOr($qb->expr()->field('title.en')->equals(new Regex($value, 'i')));
                    $qb->addOr($qb->expr()->field('title.es')->equals(new Regex($value, 'i')));
                }
            }
        }

        return $qb->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function save(MultimediaObject $multimediaObject): void
    {
        $this->objectManager->getDocumentManager()->persist($multimediaObject);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(MultimediaObject $multimediaObject): void
    {
        $this->objectManager->getDocumentManager()->remove($multimediaObject);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function findBySeriesId(string $seriesId): array
    {
        return $this->repository->findBy(['series' => $seriesId]);
    }

    public function findById(string $id): null|object
    {
        return $this->repository
            ->createQueryBuilder()
            ->field('_id')->equals(new ObjectId($id))
            ->getQuery()
            ->getSingleResult()
        ;
    }

    public function findByGroupId(string $groupId): array
    {
        return $this->repository
            ->createQueryBuilder()
            ->field('groups')->equals($groupId)
            ->getQuery()
            ->execute()
        ;
    }

    public function findPaginatedByGroupId(string $groupId, int $page, int $limit, string $sort, string $order): array
    {
        $qb = $this->repository->createQueryBuilder();

        return $qb->field('groups')->equals($groupId)
            ->sort($sort, $order)
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->hydrate(false)
            ->select('_id', 'title', 'series')
            ->getQuery()
            ->execute()
            ->toArray()
        ;
    }

    public function countByGroupId(string $groupId): int
    {
        return $this->repository
            ->createQueryBuilder()
            ->field('groups')->equals($groupId)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function matching(Criteria $criteria): array
    {
        $qb = $this->repository->createQueryBuilder();

        $this->criteriaConverter->convert($qb, $criteria);

        return $qb->getQuery()->execute()->toArray();
    }

    public function totalMatching(Criteria $criteria): int
    {
        $qb = $this->repository->createQueryBuilder();

        $this->criteriaConverter->convert($qb, new Criteria($criteria->filters()));

        return $qb->count()->getQuery()->execute();
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
