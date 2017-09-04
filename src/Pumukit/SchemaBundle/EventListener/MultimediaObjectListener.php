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

    /**
     * @param $event
     */
    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();

        if ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif (0 !== count($multimediaObject->getTracks())) {
            foreach ($multimediaObject->getTracks() as $track) {
                if ($track->isMaster() && $track->isOnlyAudio()) {
                    $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
                } elseif ($track->isMaster() && !$track->isOnlyAudio()) {
                    $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
                } else {
                    $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
                }
            }
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }

        $this->dm->flush();
    }
}
