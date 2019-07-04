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
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch create.
     *
     * Dispatchs the event TRACK_CREATE
     * 'track.create' passing
     * the multimedia object and the track
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Track $track)
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch(SchemaEvents::TRACK_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event TRACK_UPDATE
     * 'track.update' passing
     * the multimedia object and the track
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Track $track)
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch(SchemaEvents::TRACK_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event TRACK_DELETE
     * 'track.delete' passing
     * the multimedia object and the track
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Track $track)
    {
        $event = new TrackEvent($multimediaObject, $track);
        $this->dispatcher->dispatch(SchemaEvents::TRACK_DELETE, $event);
    }
}
