<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Event\GroupEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupEventDispatcherService
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
     * Dispatchs the event GROUP_CREATE
     * 'group.create' passing
     * the group
     *
     * @param Group $group
     */
    public function dispatchCreate(Group $group)
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(SchemaEvents::GROUP_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event GROUP_UPDATE
     * 'group.update' passing
     * the group
     *
     * @param Group $group
     */
    public function dispatchUpdate(Group $group)
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(SchemaEvents::GROUP_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event GROUP_DELETE
     * 'group.delete' passing
     * the group
     *
     * @param Group $group
     */
    public function dispatchDelete(Group $group)
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(SchemaEvents::GROUP_DELETE, $event);
    }
}
