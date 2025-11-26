<?php

declare(strict_types=1);

namespace App\Series\Infrastructure\Persistence;

use App\Series\Domain\Repository\SeriesStyleRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\SeriesStyle;

final class DoctrineSeriesStyleRepository implements SeriesStyleRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager) {}

    public function find(string $id): ?SeriesStyle
    {
        return $this->documentManager->getRepository(SeriesStyle::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->documentManager->getRepository(SeriesStyle::class)->findAll();
    }
}

