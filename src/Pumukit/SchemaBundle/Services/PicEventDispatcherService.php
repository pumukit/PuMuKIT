<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Event\PicEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PicEventDispatcherService
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event PIC_CREATE 'pic.create' passing the multimedia object and the pic.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_CREATE);
    }

    /**
     * Dispatch the event PIC_UPDATE 'pic.update' passing the multimedia object and the pic.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_UPDATE);
    }

    /**
     * Dispatch the event PIC_DELETE 'pic.delete' passing the multimedia object and the pic.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_DELETE);
    }
}
