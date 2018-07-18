<?php

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Doctrine\ODM\MongoDB\DocumentManager;

class MultimediaObjectListener
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    private function getTracksType($tracks)
    {
        foreach ($tracks as $track) {
            if (!$track->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }

    /**
     * @param $event
     */
    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();

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

        $this->dm->flush();
    }
}
