<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\View;

final class ViewPermissionProfileRequest
{
    public function __construct(
        public string $id,
        public string $tab = 'general'
    ) {}
}
