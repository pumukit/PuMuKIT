<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PermissionProfileEventDispatcherService
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
     * Dispatchs the event PERMISSIONPROFILE_CREATE
     * 'permissionprofile.create' passing
     * the permissionProfile
     */
    public function dispatchCreate(PermissionProfile $permissionProfile)
    {
        $event = new PermissionProfileEvent($permissionProfile);
        $this->dispatcher->dispatch(SchemaEvents::PERMISSIONPROFILE_CREATE, $event);
    }

    /**
     * Dispatch update.
     *
     * Dispatchs the event PERMISSIONPROFILE_UPDATE
     * 'permissionprofile.update' passing
     * the permissionProfile
     */
    public function dispatchUpdate(PermissionProfile $permissionProfile)
    {
        $event = new PermissionProfileEvent($permissionProfile);
        $this->dispatcher->dispatch(SchemaEvents::PERMISSIONPROFILE_UPDATE, $event);
    }

    /**
     * Dispatch delete.
     *
     * Dispatchs the event PERMISSIONPROFILE_DELETE
     * 'permissionprofile.delete' passing
     * the permissionProfile
     */
    public function dispatchDelete(PermissionProfile $permissionProfile)
    {
        $event = new PermissionProfileEvent($permissionProfile);
        $this->dispatcher->dispatch(SchemaEvents::PERMISSIONPROFILE_DELETE, $event);
    }
}
