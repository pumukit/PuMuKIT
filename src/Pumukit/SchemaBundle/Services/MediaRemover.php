<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class MediaRemover
{
    private DocumentManager $documentManager;
    private MediaDispatcher $dispatcher;

    public function __construct(DocumentManager $documentManager, MediaDispatcher $dispatcher)
    {
        $this->documentManager = $documentManager;
        $this->dispatcher = $dispatcher;
    }

    public function remove(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        if ($multimediaObject->isVideoAudioType() && $media instanceof Track) {
            $multimediaObject->removeTrack($media);
            $this->documentManager->flush();
            $this->dispatcher->remove($multimediaObject, $media);

            return;
        }

        if ($multimediaObject->isImageType() && $media instanceof Image) {
            $multimediaObject->removeImage($media);
            $this->documentManager->flush();
            $this->dispatcher->remove($multimediaObject, $media);

            return;
        }

        if ($multimediaObject->isDocumentType() && $media instanceof Document) {
            $multimediaObject->removeDocument($media);
            $this->documentManager->flush();
            $this->dispatcher->remove($multimediaObject, $media);

            return;
        }

        throw new \Exception('Unknown multimedia object type to remove media.');
    }
}
