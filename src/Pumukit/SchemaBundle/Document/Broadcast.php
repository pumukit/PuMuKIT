<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\Broadcast
 *
 * @deprecated in version 2.3
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\BroadcastRepository")
 */
class Broadcast
{
    const BROADCAST_TYPE_PUB = 'public';
    const BROADCAST_TYPE_PRI = 'private';
    const BROADCAST_TYPE_COR = 'corporative';

  /**
   * @var int $id
   *
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var ArrayCollection $multimedia_objects
   *
   * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", mappedBy="broadcast", simple=true, orphanRemoval=false)
   * @Serializer\Exclude
   */
  private $multimedia_objects;

  /**
   * @var int $number_multimedia_objects
   *
   * @MongoDB\Int
   * @MongoDB\Increment
   */
  private $number_multimedia_objects = 0;

  /**
   * @var string $name
   *
   * @MongoDB\String
   * @MongoDB\UniqueIndex(safe=1)
   */
  private $name;

  /**
   * @var string $broadcast_type_id
   *
   * @MongoDB\String
   */
  private $broadcast_type_id = self::BROADCAST_TYPE_PUB;

  /**
   * @var string $passwd
   *
   * @MongoDB\String
   */
  private $passwd;

  /**
   * @var boolean $default_sel
   *
   * @MongoDB\Boolean
   */
  private $default_sel = false;

  /**
   * @var string $description
   *
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

  /**
   * @var locale $locale
   */
  private $locale = 'en';

  /**
   *
   */
  public function __construct()
  {
      $this->multimedia_objects = new ArrayCollection();
  }

  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
      return $this->id;
  }

  /**
   * Contains multimedia_object
   *
   * @param MultimediaObject $multimedia_object
   *
   * @return boolean
   */
  public function containsMultimediaObject(MultimediaObject $multimedia_object)
  {
      return $this->multimedia_objects->contains($multimedia_object);
  }

  /**
   * Get multimedia_objects
   *
   * @return ArrayCollection
   */
  public function getMultimediaObjects()
  {
      return $this->multimedia_objects;
  }

  /**
   * Increase number_multimedia_objects
   */
  public function increaseNumberMultimediaObjects()
  {
      ++$this->number_multimedia_objects;
  }

  /**
   * Decrease number_multimedia_objects
   */
  public function decreaseNumberMultimediaObjects()
  {
      --$this->number_multimedia_objects;
  }

  /**
   * Get number_multimedia_objects
   */
  public function getNumberMultimediaObjects()
  {
      return $this->number_multimedia_objects;
  }

  /**
   * Set number_multimedia_objects
   */
  public function setNumberMultimediaObjects($count)
  {
      return $this->number_multimedia_objects = $count;
  }

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name)
  {
      $this->name = $name;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName()
  {
      return $this->name;
  }

  /**
   * Set broadcast_type_id
   *
   * @param string $broadcast_type_id
   */
  public function setBroadcastTypeId($broadcast_type_id)
  {
      $this->broadcast_type_id = $broadcast_type_id;
  }

  /**
   * Get broadcast_type_id
   *
   * @return string
   */
  public function getBroadcastTypeId()
  {
      return $this->broadcast_type_id;
  }

  /**
   * Set passwd
   *
   * @param string $passwd
   */
  public function setPasswd($passwd)
  {
      $this->passwd = $passwd;
  }

  /**
   * Get passwd
   *
   * @return string
   */
  public function getPasswd()
  {
      return $this->passwd;
  }

  /**
   * Set default_sel
   *
   * @param boolean $defatul_sel
   */
  public function setDefaultSel($default_sel)
  {
      $this->default_sel = $default_sel;
  }

  /**
   * Get default_sel
   *
   * @return boolean
   */
  public function getDefaultSel()
  {
      return $this->default_sel;
  }

  /**
   * Set description
   *
   * @param string $description
   * @param string|null $locale
   */
  public function setDescription($description, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->description[$locale] = $description;
  }

  /**
   * Get description
   *
   * @param string|null $locale
   * @return string
   */
  public function getDescription($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->description[$locale])) {
          return '';
      }

      return $this->description[$locale];
  }

  /**
   * Set I18n description
   *
   * @param array $description
   */
  public function setI18nDescription(array $description)
  {
      $this->description = $description;
  }

  /**
   * Get i18n description
   *
   * @return array
   */
  public function getI18nDescription()
  {
      return $this->description;
  }

  /**
   * Set locale
   *
   * @param string $locale
   */
  public function setLocale($locale)
  {
      $this->locale = $locale;
  }

  /**
   * Get locale
   *
   * @return string
   */
  public function getLocale()
  {
      return $this->locale;
  }

  /**
   * Clone Broadcast
   *
   * @return Broadcast
   */
  public function cloneResource()
  {
      $aux = clone $this;
      $aux->id = null;

      return $aux;
  }

  /**
   * to String
   */
  public function __toString()
  {
      return $this->getName();
  }


  /**
   * @Assert\True(message = "Password required if not public")
   */
  public function isPasswordValid()
  {
      return ((self::BROADCAST_TYPE_PUB == $this->getBroadcastTypeId())
            || ("" != $this->getPasswd()));
  }
}
