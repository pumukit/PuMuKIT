<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Event\PicEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PicEventDispatcherService
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
     * Dispatchs the event PIC_CREATE
     * 'pic.create' passing
     * the multimedia object and the pic
     *
     * @param MultimediaObject $multimediaObject
     * @param Pic              $pic
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Pic $pic)
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch(SchemaEvents::PIC_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event PIC_UPDATE
     * 'pic.update' passing
     * the multimedia object and the pic
     *
     * @param MultimediaObject $multimediaObject
     * @param Pic              $pic
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Pic $pic)
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch(SchemaEvents::PIC_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event PIC_DELETE
     * 'pic.delete' passing
     * the multimedia object and the pic
     *
     * @param MultimediaObject $multimediaObject
     * @param Pic              $pic
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Pic $pic)
    {
        $event = new PicEvent($multimediaObject, $pic);
        $this->dispatcher->dispatch(SchemaEvents::PIC_DELETE, $event);
    }
}
