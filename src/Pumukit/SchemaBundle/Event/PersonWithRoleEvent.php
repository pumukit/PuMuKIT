<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Symfony\Component\EventDispatcher\Event;

class PersonWithRoleEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Person
     */
    protected $person;

    /**
     * @var Role
     */
    protected $role;

    public function __construct(MultimediaObject $multimediaObject, PersonInterface $person, RoleInterface $role)
    {
        $this->multimediaObject = $multimediaObject;
        $this->person = $person;
        $this->role = $role;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }
}
