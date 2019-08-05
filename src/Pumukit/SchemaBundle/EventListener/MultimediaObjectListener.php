<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TextIndexService;

class MultimediaObjectListener
{
    private $dm;
    private $textIndexService;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService)
    {
        $this->dm = $dm;
        $this->textIndexService = $textIndexService;
    }

    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();
        $this->updateType($multimediaObject);
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    public function updateType(MultimediaObject $multimediaObject)
    {
        if ($multimediaObject->isLive()) {
            return;
        }

        if ($multimediaObject->getProperty('opencast')) {
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

    public function updateTextIndex(MultimediaObject $multimediaObject)
    {
        $this->textIndexService->updateMultimediaObjectTextIndex($multimediaObject);
    }

    private function getTracksType($tracks)
    {
        if (0 === count($tracks)) {
            return MultimediaObject::TYPE_UNKNOWN;
        }

        foreach ($tracks as $track) {
            if (!$track->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }
}
