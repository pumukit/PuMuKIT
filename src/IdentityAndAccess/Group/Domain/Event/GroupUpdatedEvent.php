<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\Group;

final class GroupUpdatedEvent extends DomainEvent
{
    public function __construct(
        public Group $group
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'group.updated';
    }
}
