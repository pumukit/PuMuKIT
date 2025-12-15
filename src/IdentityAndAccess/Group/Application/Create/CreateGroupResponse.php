<?php

namespace App\IdentityAndAccess\Group\Application\Create;

use Pumukit\SchemaBundle\Document\Group;

final class CreateGroupResponse
{
    public function __construct(public Group $group) {}
}
