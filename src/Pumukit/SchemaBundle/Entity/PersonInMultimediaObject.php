<?php

namespace Pumukit\SchemaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\Role
 *
 * @ORM\Table(name="person_in_multimedia_object")
 * @ORM\Entity()
 *
 */
class PersonInMultimediaObject
{
	/**
     * @var MultimediaObject $multimedia_object
     *
	 * @ORM\Id
     * @ORM\ManyToOne(targetEntity="MultimediaObject", inversedBy="id", cascade={"all"})
     * @ORM\JoinColumn(name="multimedia_object_id", referencedColumnName="id")
     */
    private $multimedia_object;
     
     /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="id", cascade={"all"})
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;
 	
 	/**
 	 * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="id", cascade={"all"})
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    private $role;

    /**
     * @var integer $rank
     *
     * @ORM\Column(name="rank", type="integer")
     */
    private $rank;


    /**
     * Set multimedia_object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function setMultimediaObject(MultimediaObject $multimedia_object)
    {
        $this->multimedia_object = $multimedia_object;
    }

    /**
     * Get multimedia_object
     *
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimedia_object;
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
     * Set role
     *
     * @param Role $role
     */
    public function setRole(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Get role
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }
}