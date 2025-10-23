<?php

namespace App\Series\Infrastructure\Persistence;

use App\Series\Domain\SeriesRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;

final class DoctrineSeriesRepository implements SeriesRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager) {}

    public function findAll(): iterable
    {
        return $this->documentManager->getRepository(Series::class)->findAll();
    }

    public function find(string $id): ?Series
    {
        return $this->documentManager->getRepository(Series::class)->find($id);
    }
}
