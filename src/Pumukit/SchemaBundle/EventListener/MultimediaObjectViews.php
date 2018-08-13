<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;

class MultimediaObjectViews
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $track = $event->getTrack();
        $multimediaObject = $event->getMultimediaObject();

        $multimediaObject->incNumview();
        $track && $track->incNumview();

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }
}
