<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Event\PicEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PicEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Dispatchs the event PIC_CREATE 'pic.create' passing the multimedia object and the pic.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_CREATE);
    }

    /**
     * Dispatchs the event PIC_UPDATE 'pic.update' passing the multimedia object and the pic.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_UPDATE);
    }

    /**
     * Dispatchs the event PIC_DELETE 'pic.delete' passing the multimedia object and the pic.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Pic $pic): void
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch($event, SchemaEvents::PIC_DELETE);
    }
}
