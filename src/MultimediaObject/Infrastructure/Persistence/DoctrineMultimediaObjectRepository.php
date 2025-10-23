<?php

namespace App\MultimediaObject\Infrastructure\Persistence;

use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class DoctrineMultimediaObjectRepository implements MultimediaObjectRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager) {}

    public function find(string $id): ?MultimediaObject
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->find($id);
    }

    public function findBySeriesId(string $seriesId): iterable
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy(['series' => new ObjectId($seriesId)]);
    }
}
