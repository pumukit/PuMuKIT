<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Series;

final class SeriesRepository
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function search(string $id)
    {
        return $this->seriesRepository()->findOneBy(['id' => new ObjectId($id)]);
    }

    private function seriesRepository()
    {
        return $this->documentManager->getRepository(Series::class);
    }
}
