<?php

namespace App\IdentityAndAccess\Authorization\Application\Create;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class CreatePermissionProfileResponse
{
    public function __construct(public PermissionProfile $permissionProfile) {}
}

