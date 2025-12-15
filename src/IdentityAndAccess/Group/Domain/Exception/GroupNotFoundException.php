<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Domain\Exception;

class GroupNotFoundException extends \Exception
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Group with id "%s" not found.', $id));
    }
}
