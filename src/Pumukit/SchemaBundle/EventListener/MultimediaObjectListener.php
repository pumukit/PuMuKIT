<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TextIndexService;

class MultimediaObjectListener
{
    private DocumentManager $dm;
    private TextIndexService $textIndexService;
    private LoggerInterface $logger;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->textIndexService = $textIndexService;
        $this->logger = $logger;
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
//        if ($multimediaObject->isLive()) {
//            return;
//        }
//
//        if ($multimediaObject->isMultistream()) {
//            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
//            return;
//        }
//
//        if($multimediaObject->getTracks()) {
//            $multimediaObject->setType($this->getTracksType($multimediaObject->getTracks()));
//            return;
//        }
//
//        if($multimediaObject->documents()) {
//            $multimediaObject->setType(MultimediaObject::TYPE_DOCUMENT);
//            return;
//        }
//
//        if($multimediaObject->images()) {
//            $multimediaObject->setType(MultimediaObject::TYPE_IMAGE);
//            return;
//        }

        if ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
            return;
        }

//        if ($displayTracks = $multimediaObject->getTracksWithTag('display')) {
//            $multimediaObject->setType($this->getTracksType($displayTracks));
//        }
//
//        if ($masterTracks = $multimediaObject->getTracksWithTag('master')) {
//            $multimediaObject->setType($this->getTracksType($masterTracks));
//        }
//        if ($otherTracks = $multimediaObject->getTracks()) {
//            $multimediaObject->setType($this->getTracksType($otherTracks));
//        }

//        $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
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
