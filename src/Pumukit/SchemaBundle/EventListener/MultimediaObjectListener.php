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
        } elseif ($track = $multimediaObject->getMaster()) {
            if ($track->isOnlyAudio()) {
                $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
            } else {
                $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
            }
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }

        $this->dm->flush();
    }
}
