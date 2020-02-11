<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Pumukit\SchemaBundle\Event\PersonWithRoleEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersonWithRoleEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event PERSONWITHROLE_CREATE 'personwithrole.create' passing the multimedia object and the personwithrole.
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, PersonInterface $person, RoleInterface $role): void
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch($event, SchemaEvents::PERSONWITHROLE_CREATE);
    }

    /**
     * Dispatch the event PERSONWITHROLE_UPDATE 'personwithrole.update' passing  the multimedia object and the personwithrole.
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, PersonInterface $person, RoleInterface $role): void
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch($event, SchemaEvents::PERSONWITHROLE_UPDATE);
    }

    /**
     * Dispatch the event PERSONWITHROLE_DELETE 'personwithrole.delete' passing the multimedia object and the personwithrole.
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, PersonInterface $person, RoleInterface $role): void
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch($event, SchemaEvents::PERSONWITHROLE_DELETE);
    }
}
