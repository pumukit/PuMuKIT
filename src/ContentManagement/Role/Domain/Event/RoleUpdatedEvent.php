<?php

namespace App\ContentManagement\Role\Domain\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Contracts\EventDispatcher\Event;

final class RoleUpdatedEvent extends Event
{
    public const NAME = 'role.updated';

    public function __construct(
        private Role $role
    ) {}

    public function getRole(): Role
    {
        return $this->role;
    }
}
