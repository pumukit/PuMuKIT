<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Group;
use Symfony\Component\EventDispatcher\Event;

class GroupEvent extends Event
{
    /**
     * @var Group
     */
    protected $group;

    /**
     * @param Group $group
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
