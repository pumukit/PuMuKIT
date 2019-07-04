<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Event\PersonWithRoleEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersonWithRoleEventDispatcherService
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
     * Dispatchs the event PERSONWITHROLE_CREATE
     * 'personwithrole.create' passing
     * the multimedia object and the personwithrole
     *
     * @param MultimediaObject      $multimediaObject
     * @param embeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function dispatchCreate(MultimediaObject $multimediaObject, $person, $role)
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch(SchemaEvents::PERSONWITHROLE_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event PERSONWITHROLE_UPDATE
     * 'personwithrole.update' passing
     * the multimedia object and the personwithrole
     *
     * @param MultimediaObject      $multimediaObject
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function dispatchUpdate(MultimediaObject $multimediaObject, $person, $role)
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch(SchemaEvents::PERSONWITHROLE_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event PERSONWITHROLE_DELETE
     * 'personwithrole.delete' passing
     * the multimedia object and the personwithrole
     *
     * @param MultimediaObject      $multimediaObject
     * @param EmbeddedPerson|Person $person
     * @param EmbeddedRole|Role     $role
     */
    public function dispatchDelete(MultimediaObject $multimediaObject, $person, $role)
    {
        $event = new PersonWithRoleEvent($multimediaObject, $person, $role);
        $this->dispatcher->dispatch(SchemaEvents::PERSONWITHROLE_DELETE, $event);
    }
}
