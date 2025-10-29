<?php

namespace App\User\Application\List;

final class ListUserResponse
{
    public array $users;
    public int $total;

    public function __construct(array $users, int $total)
    {
        $this->users = $users;
        $this->total = $total;
    }
}
