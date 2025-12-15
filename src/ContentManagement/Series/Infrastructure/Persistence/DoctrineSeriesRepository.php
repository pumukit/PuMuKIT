<?php

namespace App\ContentManagement\Series\Infrastructure\Persistence;

use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

final class DoctrineSeriesRepository implements SeriesRepositoryInterface
{
    public const FIELD_MAPPING = [
        'oneSeries.title' => 'title',
        'oneSeries.publicDate' => 'public_date',
    ];

    public function __construct(private DoctrineObjectManager $objectManager) {}

    public function findAll(): iterable
    {
        return $this->objectManager->getRepository(Series::class)->findAll();
    }

    public function find(string $id): ?Series
    {
        return $this->objectManager->find(Series::class, $id);
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Series::class);

        if (!empty($filters['title'])) {
            $qb->field('title.es')->equals($filters['title']);
        }

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function countMultimediaObjects(string $seriesId): int
    {
        return $this->objectManager->getDocumentManager()->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('series')->equals(new ObjectId($seriesId))
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function countEventMultimediaObjects(string $seriesId): int
    {
        return $this->objectManager->getDocumentManager()->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('series')->equals(new ObjectId($seriesId))
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->equals(MultimediaObject::TYPE_LIVE)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, ?string $sort = null, ?string $order = null): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Series::class);

        foreach ($filters as $field => $value) {
            $qb->field($field)->equals($value);
        }

        if ($sort && $order) {
            $realSortField = self::FIELD_MAPPING[$sort] ?? $sort;
            $qb->sort($realSortField, 'asc' === $order ? 'ASC' : 'DESC');
        }

        $qb->skip(($page - 1) * $limit)->limit($limit);

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function countByFilters(array $filters): int
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Series::class);

        foreach ($filters as $field => $value) {
            $qb->field($field)->equals($value);
        }

        return $qb->count()->getQuery()->execute();
    }

    public function findMultimediaObjectsBySeries(string $seriesId, int $offset = 0, int $limit = 10, string $sort = 'title', string $order = 'asc'): array
    {
        $fieldMapping = [
            'title' => 'title',
            'status' => 'status',
            'type' => 'type',
        ];

        $sortField = $fieldMapping[$sort] ?? 'title';
        $sortDirection = 'asc' === strtolower($order) ? 1 : -1;

        $qb = $this->objectManager->getDocumentManager()
            ->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->field('series')->equals(new ObjectId($seriesId))
            ->skip($offset)
            ->limit($limit)
            ->sort($sortField, $sortDirection)
        ;

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function findEventsBySeries(string $seriesId, int $offset = 0, int $limit = 10, string $sort = 'title', string $order = 'asc'): array
    {
        $fieldMapping = [
            'title' => 'title',
            'status' => 'status',
            'type' => 'type',
        ];

        $sortField = $fieldMapping[$sort] ?? 'title';
        $sortDirection = 'asc' === strtolower($order) ? 1 : -1;

        $qb = $this->objectManager->getDocumentManager()
            ->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->equals(MultimediaObject::TYPE_LIVE)
            ->field('series')->equals(new ObjectId($seriesId))
            ->skip($offset)
            ->limit($limit)
            ->sort($sortField, $sortDirection)
        ;

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function delete(Series $series): void
    {
        $this->objectManager->getDocumentManager()->remove($series);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function countEvents(string $seriesId): int
    {
        return $this->objectManager->getDocumentManager()->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('series')->equals(new ObjectId($seriesId))
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->equals(MultimediaObject::TYPE_LIVE)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function save(Series $series): void
    {
        $this->objectManager->getDocumentManager()->persist($series);
        $this->objectManager->getDocumentManager()->flush();
    }
}
