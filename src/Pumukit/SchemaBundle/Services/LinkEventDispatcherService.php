<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\LinkEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LinkEventDispatcherService
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch create.
     *
     * Dispatchs the event LINK_CREATE
     * 'link.create' passing
     * the multimedia object and the link
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Link $link)
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch(SchemaEvents::LINK_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event LINK_UPDATE
     * 'link.update' passing
     * the multimedia object and the link
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Link $link)
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch(SchemaEvents::LINK_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event LINK_DELETE
     * 'link.delete' passing
     * the multimedia object and the link
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Link $link)
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch(SchemaEvents::LINK_DELETE, $event);
    }
}
