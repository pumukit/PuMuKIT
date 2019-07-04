<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Component\EventDispatcher\Event;

class PermissionProfileEvent extends Event
{
    /**
     * @var PermissionProfile
     */
    protected $permissionProfile;

    /**
     * @param PermissionProfile $permissionProfile
     */
    public function __construct(PermissionProfile $permissionProfile)
    {
        $this->permissionProfile = $permissionProfile;
    }

    /**
     * @return PermissionProfile
     */
    public function getPermissionProfile()
    {
        return $this->permissionProfile;
    }
}
