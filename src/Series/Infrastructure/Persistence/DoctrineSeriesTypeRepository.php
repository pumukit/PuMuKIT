<?php

declare(strict_types=1);

namespace App\Series\Infrastructure\Persistence;

use App\Series\Domain\Repository\SeriesTypeRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\SeriesType;

final class DoctrineSeriesTypeRepository implements SeriesTypeRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager) {}

    public function find(string $id): ?SeriesType
    {
        return $this->documentManager->getRepository(SeriesType::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->documentManager->getRepository(SeriesType::class)->findAll();
    }
}

