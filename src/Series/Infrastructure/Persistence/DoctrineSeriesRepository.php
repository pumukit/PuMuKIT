<?php

namespace App\Series\Infrastructure\Persistence;

use App\Series\Domain\SeriesRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

final class DoctrineSeriesRepository implements SeriesRepositoryInterface
{
    public const FIELD_MAPPING = [
        'oneSeries.title' => 'title',
        'oneSeries.publicDate' => 'public_date',
    ];

    public function __construct(private DocumentManager $documentManager) {}

    public function findAll(): iterable
    {
        return $this->documentManager->getRepository(Series::class)->findAll();
    }

    public function find(string $id): ?Series
    {
        return $this->documentManager->getRepository(Series::class)->find($id);
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->documentManager->createQueryBuilder(Series::class);

        if (!empty($filters['title'])) {
            $qb->field('title.es')->equals($filters['title']);
        }

        return $qb->getQuery()->execute()->toArray();
    }

    public function findAllPaginated(int $page, int $limit): array
    {
        $qb = $this->documentManager->createQueryBuilder(Series::class)
            ->skip(($page - 1) * $limit)
            ->limit($limit)
        ;

        return $qb->getQuery()->execute()->toArray();
    }

    public function countAll(): int
    {
        return $this->documentManager->createQueryBuilder(Series::class)->count()->getQuery()->execute();
    }

    public function countMultimediaObjects(string $serieId): int
    {
        return $this->documentManager->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('series')->equals(new ObjectId($serieId))
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function countEventMultimediaObjects(string $serieId): int
    {
        return $this->documentManager->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('series')->equals(new ObjectId($serieId))
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->equals(MultimediaObject::TYPE_LIVE)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, ?string $sort = null, ?string $order = null): array
    {
        $qb = $this->documentManager->createQueryBuilder(Series::class);

        foreach ($filters as $field => $value) {
            $qb->field($field)->equals($value);
        }

        if ($sort && $order) {
            $realSortField = self::FIELD_MAPPING[$sort] ?? $sort;
            $qb->sort($realSortField, 'asc' === $order ? 'ASC' : 'DESC');
        }

        $qb->skip(($page - 1) * $limit)->limit($limit);

        return $qb->getQuery()->execute()->toArray();
    }

    public function countByFilters(array $filters): int
    {
        $qb = $this->documentManager->createQueryBuilder(Series::class);

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
        $sortDirection = strtolower($order) === 'asc' ? 1 : -1;

        $qb = $this->documentManager
            ->getRepository(MultimediaObject::class)
            ->createQueryBuilder()
            ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->field('series')->equals(new ObjectId($seriesId))
            ->skip($offset)
            ->limit($limit)
            ->sort($sortField, $sortDirection);

        return $qb->getQuery()->execute()->toArray();
    }

    public function delete(Series $series): void
    {
        $this->documentManager->remove($series);
        $this->documentManager->flush();
    }
}
