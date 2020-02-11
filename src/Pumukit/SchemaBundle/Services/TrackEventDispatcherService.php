<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TrackEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatchs the event TRACK_CREATE 'track.create' passing the multimedia object and the track.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Track $track): void
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_CREATE);
    }

    /**
     * Dispatchs the event TRACK_UPDATE 'track.update' passing the multimedia object and the track.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Track $track): void
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_UPDATE);
    }

    /**
     * Dispatchs the event TRACK_DELETE 'track.delete' passing the multimedia object and the track.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Track $track): void
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_DELETE);
    }
}
