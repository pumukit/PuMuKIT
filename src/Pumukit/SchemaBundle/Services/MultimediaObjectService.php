<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class MultimediaObjectService
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
