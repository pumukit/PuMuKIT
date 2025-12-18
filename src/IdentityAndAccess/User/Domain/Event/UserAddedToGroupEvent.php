<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\User;

final class UserAddedToGroupEvent extends DomainEvent
{
    public function __construct(
        public User $user
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'user.added_to_group';
    }
}
