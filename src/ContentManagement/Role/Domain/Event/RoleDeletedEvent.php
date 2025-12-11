<?php

namespace App\ContentManagement\Role\Domain\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Contracts\EventDispatcher\Event;

final class RoleDeletedEvent extends Event
{
    public const NAME = 'role.deleted';

    public function __construct(
        private Role $role
    ) {}

    public function getRole(): Role
    {
        return $this->role;
    }
}
