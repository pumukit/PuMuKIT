<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\RoleRepository")
 */
class Role
{

  /**
   * @MongoDB\Id
   */
  protected $id;

  /**
   * @var string $cod
   *
   * @MongoDB\String
   */
  private $cod = 0;

  /**
   * @var integer $rank
   *
   * @MongoDB\Int
   * @Gedmo\SortablePosition
   */
  private $rank;

  /**
   * See European Broadcasting Union Role Codes
   * @var string $xml
   *
   * @MongoDB\String
   */
  private $xml;

  /**
   * @var boolean $display
   *
   * @MongoDB\Boolean
   */
  private $display = true;

  /**
   * @var string $name
   *
   * @MongoDB\Raw
   */
  private $name = array('en'=>'');

  /**
   * @var string $text
   *
   * @MongoDB\Raw
   */
  private $text = array('en'=>'');


  /**
   * @var ArrayCollection $person_in_multimedia_object
   *
   * @MongoDB\ReferenceMany(targetDocument="PersonInMultimediaObject", mappedBy="role")
   */
  private $people_in_multimedia_object;

  /**
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   * @var locale $locale
   */
  private $locale = 'en';

  /**
   * Geto id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set cod
   *
   * @param string $cod
   */
  public function setCod($cod)
  {
    $this->cod = $cod;
  }

  /**
   * Get cod
   *
   * @return string
   */
  public function getCod()
  {
    return $this->cod;
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

  /**
   * Set xml
   *
   * @param string $xml
   */
  public function setXml($xml)
  {
    $this->xml = $xml;
  }

  /**
   * Get xml
   *
   * @return string
   */
  public function getXml()
  {
    return $this->xml;
  }

  /**
   * Set display
   *
   * @param boolean $display
   */
  public function setDisplay($display)
  {
    $this->display = $display;
  }

  /**
   * Get display
   *
   * @return boolean
   */
  public function getDisplay()
  {
    return $this->display;
  }

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->name[$locale] = $name;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->name[$locale])){
      return null;
    }
    return $this->name[$locale];
  }

  /**
   * Set I18n name
   *
   * @param array $name
   */
  public function setI18nName(array $name)
  {
    $this->name = $name;
  }
  
  /**
   * Get i18n name
   *
   * @return array
   */
  public function getI18nName()
  {
    return $this->name;
  }

  /**
   * Set text
   *
   * @param string $text
   */
  public function setText($text, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->text[$locale] = $text;
  }

  /**
   * Get text
   *
   * @return string
   */
  public function getText($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->text[$locale])){
      return null;
    }
    return $this->text[$locale];
  }

  /**
   * Set I18n text
   *
   * @param array $text
   */
  public function setI18nText(array $text)
  {
    $this->text = $text;
  }
  
  /**
   * Get i18n text
   *
   * @return array
   */
  public function getI18nText()
  {
    return $this->text;
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
   * Constructor
   */
  public function __construct()
  {
    $this->people_in_multimedia_object = new ArrayCollection();
  }

  /**
   * Add people_in_multimedia_object
   *
   * @param PersonInMultimediaObject $peopleInMultimediaObject
   * @return Role
   */
  public function addPeopleInMultimediaObject(PersonInMultimediaObject $peopleInMultimediaObject)
  {
    $this->people_in_multimedia_object[] = $peopleInMultimediaObject;

    return $this;
  }

  /**
   * Remove people_in_multimedia_object
   *
   * @param PersonInMultimediaObject $peopleInMultimediaObject
   */
  public function removePeopleInMultimediaObject(PersonInMultimediaObject $peopleInMultimediaObject)
  {
    $this->people_in_multimedia_object->removeElement($peopleInMultimediaObject);
  }

  /**
   * Get people_in_multimedia_object
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getPeopleInMultimediaObject()
  {
    return $this->people_in_multimedia_object;
  }

  /**
   * Clone Role
   *
   * @return Role
   */
  public function cloneResource()
  {
    $aux = clone $this;
    $aux->id = null;

    return $aux;
  }

}
