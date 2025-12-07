<?php

namespace App\IdentityAndAccess\Application\Create;

use Pumukit\SchemaBundle\Document\User;

final class CreateUserResponse
{
    public function __construct(public readonly User $user) {}
}
