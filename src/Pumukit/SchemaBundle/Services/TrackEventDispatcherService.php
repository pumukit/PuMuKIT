<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TrackEventDispatcherService
{
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event TRACK_CREATE 'track.create' passing the multimedia object and the track.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new TrackEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_CREATE);
    }

    /**
     * Dispatch the event TRACK_UPDATE 'track.update' passing the multimedia object and the track.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new TrackEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_UPDATE);
    }

    /**
     * Dispatch the event TRACK_DELETE 'track.delete' passing the multimedia object and the track.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new TrackEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::TRACK_DELETE);
    }
}
