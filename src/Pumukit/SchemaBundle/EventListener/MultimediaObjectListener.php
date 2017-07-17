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

    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();

        if (isset($multimediaObject->getProperties()['externalplayer'])) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif (0 !== count($multimediaObject->getTracks())) {
            if (!$multimediaObject->isOnlyAudio()) {
                $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
            } elseif ($multimediaObject->isOnlyAudio()) {
                $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
            }
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }

        $this->dm->flush();
    }
}
