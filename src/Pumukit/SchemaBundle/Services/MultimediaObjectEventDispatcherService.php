<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use FOS\UserBundle\Model\UserInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MultimediaObjectAddOwnerEvent;
use Pumukit\SchemaBundle\Event\MultimediaObjectCloneEvent;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MultimediaObjectEventDispatcherService
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event MULTIMEDIAOBJECT_CREATE 'multimediaobject.create' passing the multimedia object.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject): void
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch($event, SchemaEvents::MULTIMEDIAOBJECT_CREATE);
    }

    /**
     * Dispatch the event MULTIMEDIAOBJECT_UPDATE 'multimediaobject.update' passing the multimedia object.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject): void
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch($event, SchemaEvents::MULTIMEDIAOBJECT_UPDATE);
    }

    /**
     * Dispatch the event MULTIMEDIAOBJECT_DELETE 'multimediaobject.delete' passing the multimedia object.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject): void
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->dispatcher->dispatch($event, SchemaEvents::MULTIMEDIAOBJECT_DELETE);
    }

    /**
     * Dispatch the event MULTIMEDIAOBJECT_CLONE 'multimediaobject.clone' passing the multimedia object.
     */
    public function dispatchClone(MultimediaObject $multimediaObject, MultimediaObject $multimediaObjectCloned): void
    {
        $event = new MultimediaObjectCloneEvent($multimediaObject, $multimediaObjectCloned);
        $this->dispatcher->dispatch($event, SchemaEvents::MULTIMEDIAOBJECT_CLONE);
    }

    public function dispatchMultimediaObjectAddOwner(MultimediaObject $multimediaObject, UserInterface $user, UserInterface $coOwner): void
    {
        $event = new MultimediaObjectAddOwnerEvent($multimediaObject, $user, $coOwner);
        $this->dispatcher->dispatch(SchemaEvents::MULTIMEDIA_OBJECT_ADD_OWNER, $event);
    }
}
