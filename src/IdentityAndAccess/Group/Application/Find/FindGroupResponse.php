<?php

namespace App\IdentityAndAccess\Group\Application\Find;

use Pumukit\SchemaBundle\Document\Group;

final class FindGroupResponse
{
    public function __construct(public Group $group) {}
}
