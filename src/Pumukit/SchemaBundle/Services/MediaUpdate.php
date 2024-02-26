<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

final class MediaUpdate
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function updateMediaTags(MultimediaObject $multimediaObject, MediaInterface $media, Tags $tags): void
    {
        $media->updateTags($tags);
        $this->documentManager->flush();
    }
}
