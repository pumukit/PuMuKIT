<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\PersonInMultimediaObject
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\PersonInMultimediaObjectRepository")
 */
class PersonInMultimediaObject
{
    /**
   * @MongoDB\Id
   */
  protected $id;

  /**
   * @var MultimediaObject $multimedia_object
   *
   * @MongoDB\Id
   * @MongoDB\ReferenceOne(targetDocument="MultimediaObject", mappedBy="people_in_multimedia_object")
   */
  private $multimedia_object;

  /**
   * @MongoDB\Id
   * @MongoDB\EmbedOne(targetDocument="Person")
   */
  private $person;

  /**
   * @MongoDB\Id
   * @MongoDB\ReferenceOne(targetDocument="Role", inversedBy="people_in_multimedia_object")
   */
  private $role;

  /**
   * @var int $rank
   *
   * @MongoDB\Int
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
   * Get id
   *
   * @return Id
   */
  public function getId()
  {
      return $this->id;
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
   * @param int $rank
   */
  public function setRank($rank)
  {
      $this->rank = $rank;
  }

  /**
   * Get rank
   *
   * @return int
   */
  public function getRank()
  {
      return $this->rank;
  }
}
