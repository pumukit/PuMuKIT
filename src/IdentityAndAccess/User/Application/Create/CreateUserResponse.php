<?php

namespace App\IdentityAndAccess\User\Application\Create;

use Pumukit\SchemaBundle\Document\User;

final class CreateUserResponse
{
    public function __construct(public User $user) {}
}
