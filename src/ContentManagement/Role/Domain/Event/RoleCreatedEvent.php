<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Role;

final class RoleCreatedEvent extends DomainEvent
{
    public function __construct(
        public Role $role
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'role.created';
    }
}
