<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PersonWithRoleEvent extends Event
{
    /** @var MultimediaObject */
    protected $multimediaObject;

    /** @var PersonInterface */
    protected $person;

    /** @var RoleInterface */
    protected $role;

    public function __construct(MultimediaObject $multimediaObject, PersonInterface $person, RoleInterface $role)
    {
        $this->multimediaObject = $multimediaObject;
        $this->person = $person;
        $this->role = $role;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getPerson(): PersonInterface
    {
        return $this->person;
    }

    public function getRole(): RoleInterface
    {
        return $this->role;
    }
}
