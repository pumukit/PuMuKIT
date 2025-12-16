<?php

namespace App\IdentityAndAccess\Authorization\Application\Update;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class UpdatePermissionProfileResponse
{
    public function __construct(public PermissionProfile $permissionProfile) {}
}
