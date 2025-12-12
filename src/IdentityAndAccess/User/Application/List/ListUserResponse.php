<?php

namespace App\IdentityAndAccess\User\Application\List;

final class ListUserResponse
{
    public function __construct(public array $users, public int $total)
    {
    }
}
