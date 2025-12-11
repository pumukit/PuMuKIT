<?php

namespace App\IdentityAndAccess\User\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct("User with id '{$userId}' not found");
    }
}
