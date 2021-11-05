<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Group;
use Symfony\Contracts\EventDispatcher\Event;

class GroupEvent extends Event
{
    /**
     * @var Group
     */
    protected $group;

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
