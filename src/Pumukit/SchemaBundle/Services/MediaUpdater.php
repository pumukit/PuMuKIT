<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

final class MediaUpdater
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function updateId(MultimediaObject $multimediaObject, MediaInterface $media, string $id): void
    {
        $media->updateId($id);
        $this->documentManager->flush();
    }

    public function updateTags(MultimediaObject $multimediaObject, MediaInterface $media, Tags $tags): void
    {
        $media->updateTags($tags);
        $this->documentManager->flush();
    }

    public function updateDescription(MultimediaObject $multimediaObject, MediaInterface $media, i18nText $description): void
    {
        $media->updateDescription($description);
        $this->documentManager->flush();
    }

    public function updateLanguage(MultimediaObject $multimediaObject, MediaInterface $media, string $language): void
    {
        $media->updateLanguage($language);
        $this->documentManager->flush();
    }

    public function updateHide(MultimediaObject $multimediaObject, MediaInterface $media, bool $hide): void
    {
        $media->updateHide($hide);
        $this->documentManager->flush();
    }

    public function updateDownload(MultimediaObject $multimediaObject, MediaInterface $media, bool $download): void
    {
        $media->updateDownload($download);
        $this->documentManager->flush();
    }

    public function updateStorage(MultimediaObject $multimediaObject, MediaInterface $media, Storage $storage): void
    {
        $media->updateStorage($storage);
        $this->documentManager->flush();
    }

    public function updateMetadata(MultimediaObject $multimediaObject, MediaInterface $media, MediaMetadata $metadata): void
    {
        $media->updateMetadata($metadata);
        $this->documentManager->flush();
    }
}
