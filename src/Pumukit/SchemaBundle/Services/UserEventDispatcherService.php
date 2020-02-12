<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UserEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Dispatch the event USER_CREATE 'user.create' passing the user.
     */
    public function dispatchCreate(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch($event, SchemaEvents::USER_CREATE);
    }

    /**
     * Dispatch the event USER_UPDATE 'user.update' passing the user.
     */
    public function dispatchUpdate(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch($event, SchemaEvents::USER_UPDATE);
    }

    /**
     * Dispatch the event USER_DELETE 'user.delete' passing the user.
     */
    public function dispatchDelete(User $user)
    {
        $event = new UserEvent($user);
        $this->dispatcher->dispatch($event, SchemaEvents::USER_DELETE);
    }
}
