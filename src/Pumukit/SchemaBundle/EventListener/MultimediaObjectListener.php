<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TextIndexService;

class MultimediaObjectListener
{
    private DocumentManager $dm;
    private TextIndexService $textIndexService;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService)
    {
        $this->dm = $dm;
        $this->textIndexService = $textIndexService;
    }

    public function postUpdate($event): void
    {
        $multimediaObject = $event->getMultimediaObject();
        $this->updateType($multimediaObject);
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    public function updateType(MultimediaObject $multimediaObject): void
    {
        if ($multimediaObject->isLive()) {
            return;
        }

        if ($multimediaObject->isMultistream()) {
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        } elseif ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif ($displayTracks = $multimediaObject->getTracksWithTag('display')) {
            $multimediaObject->setType($this->getTracksType($displayTracks));
        } elseif ($masterTracks = $multimediaObject->getTracksWithTag('master')) {
            $multimediaObject->setType($this->getTracksType($masterTracks));
        } elseif ($otherTracks = $multimediaObject->getTracks()) {
            $multimediaObject->setType($this->getTracksType($otherTracks));
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }
    }

    public function updateTextIndex(MultimediaObject $multimediaObject): void
    {
        $this->textIndexService->updateMultimediaObjectTextIndex($multimediaObject);
    }

    private function getTracksType($tracks): int
    {
        if (0 === (is_countable($tracks) ? count($tracks) : 0)) {
            return MultimediaObject::TYPE_UNKNOWN;
        }

        foreach ($tracks as $track) {
            if (!$track->metadata()->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }
}
