<?php

declare(strict_types=1);

namespace App\MultimediaObject\Infrastructure\Persistence;

use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class DoctrineMultimediaObjectRepository implements MultimediaObjectRepositoryInterface
{
    public function __construct(private DoctrineObjectManager $objectManager) {}

    public function find(string $id): ?MultimediaObject
    {
        return $this->objectManager->getDocumentManager()
            ->getRepository(MultimediaObject::class)
            ->find($id)
        ;
    }

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null, ?array $filters = []): iterable
    {
        $qb = $this->objectManager->getDocumentManager()
            ->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
        ;

        $qb->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE);
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
        $qb = $this->objectManager->getDocumentManager()
            ->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
        ;

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
