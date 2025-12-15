<?php

namespace App\IdentityAndAccess\Group\Application\Update;

use Pumukit\SchemaBundle\Document\Group;

final class UpdateGroupResponse
{
    public function __construct(public Group $group) {}
}
