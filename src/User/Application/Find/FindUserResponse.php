<?php

namespace App\User\Application\Find;

use Pumukit\SchemaBundle\Document\User;

final class FindUserResponse
{
    public function __construct(public readonly User $user) {}
}
