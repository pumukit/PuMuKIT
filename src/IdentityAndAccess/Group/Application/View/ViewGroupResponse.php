<?php

namespace App\IdentityAndAccess\Group\Application\View;

use Pumukit\SchemaBundle\Document\Group;

final class ViewGroupResponse
{
    public function __construct(
        public Group $group,
        public string $tab
    ) {}
}
