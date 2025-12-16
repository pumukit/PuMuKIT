<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\View;

final class ViewGroupRequest
{
    public function __construct(
        public string $id,
        public string $tab = 'general'
    ) {}
}
