<?php

namespace App\IdentityAndAccess\User\Application\View;

use Pumukit\SchemaBundle\Document\User;

final class ViewUserResponse
{
    public function __construct(public readonly User $user) {}
}
