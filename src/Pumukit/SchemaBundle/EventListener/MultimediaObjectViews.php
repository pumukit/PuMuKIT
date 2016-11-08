<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;

class MultimediaObjectViews
{
    private $mm_manager;

    public function __construct(MultimediaObjectService $mm_manager)
    {
        $this->mm_manager = $mm_manager;
    }

    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $track = $event->getTrack();
        $multimediaObject = $event->getMultimediaObject();

        if (!$this->isViewableTrack($track)) {
            return;
        }

        $multimediaObject->incNumview();
        $track && $track->incNumview();

        $this->mm_manager->updateMultimediaObject($multimediaObject);
    }

    private function isViewableTrack(Track $track = null)
    {
        return !$track || !$track->containsTag('presentation/delivery');
    }
}
