<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Document\User as BaseUser;

/**
 * Pumukit\SchemaBundle\Document\User
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
    /**
     * @var int $id
     *
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Person", inversedBy="user", simple=true)
     */
    private $person;

    /**
     * @var string $fullname
     *
     * @MongoDB\String
     */
    protected $fullname;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set person
     *
     * @param Person $person
     */
    public function setPerson(Person $person)
    {
        $this->person = $person;
    }

    /**
     * Get person
     *
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }
}
