<?php

namespace App\IdentityAndAccess\Application\View;

use Pumukit\SchemaBundle\Document\User;

final class ViewUserResponse
{
    public function __construct(public readonly User $user) {}
}
