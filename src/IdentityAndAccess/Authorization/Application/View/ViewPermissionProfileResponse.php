<?php

namespace App\IdentityAndAccess\Authorization\Application\View;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class ViewPermissionProfileResponse
{
    public function __construct(
        public PermissionProfile $permissionProfile,
        public string $tab
    ) {}
}

