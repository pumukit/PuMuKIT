<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserEventDispatcherService
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
     * Dispatchs the event USER_CREATE
     * 'user.create' passing
     * the user
     */
    public function dispatchCreate(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch(SchemaEvents::USER_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event USER_UPDATE
     * 'user.update' passing
     * the user
     */
    public function dispatchUpdate(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch(SchemaEvents::USER_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event USER_DELETE
     * 'user.delete' passing
     * the user
     */
    public function dispatchDelete(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch(SchemaEvents::USER_DELETE, $event);
    }
}
