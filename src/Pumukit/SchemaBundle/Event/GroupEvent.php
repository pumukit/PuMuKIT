<?php

namespace Pumukit\SchemaBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\Group;

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
