<?php

namespace App\IdentityAndAccess\Authorization\Application\Find;

use Pumukit\SchemaBundle\Document\PermissionProfile;

final class FindPermissionProfileResponse
{
    public function __construct(public PermissionProfile $permissionProfile) {}
}
