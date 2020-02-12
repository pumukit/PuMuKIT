<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Event\GroupEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GroupEventDispatcherService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Dispatch the event GROUP_CREATE 'group.create' passing the group.
     */
    public function dispatchCreate(Group $group): void
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch($event, SchemaEvents::GROUP_CREATE);
    }

    /**
     * Dispatch the event GROUP_UPDATE 'group.update' passing the group.
     */
    public function dispatchUpdate(Group $group): void
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch($event, SchemaEvents::GROUP_UPDATE);
    }

    /**
     * Dispatch the event GROUP_DELETE 'group.delete' passing the group.
     */
    public function dispatchDelete(Group $group): void
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch($event, SchemaEvents::GROUP_DELETE);
    }
}
