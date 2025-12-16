<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Domain\Exception;

use Exception;

class PermissionProfileNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('PermissionProfile with id "%s" not found.', $id));
    }
}

