<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Persistence;

use App\ContentManagement\Series\Domain\Repository\SeriesTypeRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use Pumukit\SchemaBundle\Document\SeriesType;

final class DoctrineSeriesTypeRepository implements SeriesTypeRepositoryInterface
{
    public function __construct(private DoctrineObjectManager $objectManager) {}

    public function find(string $id): ?SeriesType
    {
        return $this->objectManager->getDocumentManager()->getRepository(SeriesType::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->objectManager->getDocumentManager()->getRepository(SeriesType::class)->findAll();
    }
}
