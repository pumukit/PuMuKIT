<?php

namespace App\IdentityAndAccess\Group\Application\Delete;

final class DeleteGroupResponse
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}
}
