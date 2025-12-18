<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Domain\Event;

use App\Shared\Domain\DomainEvent;
use Pumukit\SchemaBundle\Document\User;

final class UserCreatedEvent extends DomainEvent
{
    public function __construct(
        public User $user
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'user.created';
    }
}
