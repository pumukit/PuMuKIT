<?php

namespace Pumukit\SchemaBundle\EventListener;

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
