<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Contracts\EventDispatcher\Event;

class PermissionProfileEvent extends Event
{
    /**
     * @var PermissionProfile
     */
    protected $permissionProfile;

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
