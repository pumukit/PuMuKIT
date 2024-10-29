<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MediaEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MediaDispatcher
{
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function create(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new MediaEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::MEDIA_CREATE);
    }

    public function update(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new MediaEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::MEDIA_UPDATE);
    }

    public function remove(MultimediaObject $multimediaObject, MediaInterface $media): void
    {
        $event = new MediaEvent($multimediaObject, $media);
        $this->dispatcher->dispatch($event, SchemaEvents::MEDIA_REMOVE);
    }
}
