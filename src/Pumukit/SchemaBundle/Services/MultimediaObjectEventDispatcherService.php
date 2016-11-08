<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectEventDispatcherService
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch create
     *
     * Dispatchs the event MULTIMEDIAOBJECT_CREATE
     * 'multimediaobject.create' passing
     * the multimedia object
     *
     * @param MultimediaObject $multimediaObject
     */
    public function dispatchCreate(MultimediaObject $multimediaObject)
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch(SchemaEvents::MULTIMEDIAOBJECT_CREATE, $event);
    }

    /**
     * Dispatch update
     *
     * Dispatchs the event MULTIMEDIAOBJECT_UPDATE
     * 'multimediaobject.update' passing
     * the multimedia object
     *
     * @param MultimediaObject $multimediaObject
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject)
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, $event);
    }

    /**
     * Dispatch delete
     *
     * Dispatchs the event MULTIMEDIAOBJECT_DELETE
     * 'multimediaobject.delete' passing
     * the multimedia object
     *
     * @param MultimediaObject $multimediaObject
     */
    public function dispatchDelete(MultimediaObject $multimediaObject)
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch(SchemaEvents::MULTIMEDIAOBJECT_DELETE, $event);
    }
}
