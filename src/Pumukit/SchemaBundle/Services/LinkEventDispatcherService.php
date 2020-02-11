<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\LinkEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LinkEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event LINK_CREATE 'link.create' passing the multimedia object and the link.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, Link $link): void
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch($event, SchemaEvents::LINK_CREATE);
    }

    /**
     * Dispatch the event LINK_UPDATE 'link.update' passing the multimedia object and the link.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, Link $link): void
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch($event, SchemaEvents::LINK_UPDATE);
    }

    /**
     * Dispatch the event LINK_DELETE 'link.delete' passing the multimedia object and the link.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, Link $link): void
    {
        $event = new LinkEvent($multimediaObject, $link);
        $this->dispatcher->dispatch($event, SchemaEvents::LINK_DELETE);
    }
}
