<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Persistence;

use App\ContentManagement\Series\Domain\Repository\SeriesStyleRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use Pumukit\SchemaBundle\Document\SeriesStyle;

final class DoctrineSeriesStyleRepository implements SeriesStyleRepositoryInterface
{
    public function __construct(private DoctrineObjectManager $objectManager) {}

    public function find(string $id): ?SeriesStyle
    {
        return $this->objectManager->getDocumentManager()->getRepository(SeriesStyle::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->objectManager->getDocumentManager()->getRepository(SeriesStyle::class)->findAll();
    }
}
