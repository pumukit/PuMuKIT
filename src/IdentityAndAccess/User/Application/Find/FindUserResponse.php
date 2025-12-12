<?php

namespace App\IdentityAndAccess\User\Application\Find;

use Pumukit\SchemaBundle\Document\User;

final class FindUserResponse
{
    public function __construct(public User $user) {}
}
