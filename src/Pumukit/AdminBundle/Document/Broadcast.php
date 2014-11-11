<?php

namespace Pumukit\AdminBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Pumukit\AdminBundle\Document\Broadcast
 *
 * @MongoDB\Document(repositoryClass="Pumukit\AdminBundle\Repository\BroadcastRepository")
 */
class Broadcast
{

  /** 
   * @var int $id
   * 
   * @MongoDB\Id
   */
  private $id;

  /** 
   * @var string $name
   * 
   * @MongoDB\String
   */
  private $name;

  /** 
   * @var int $broadcast_type_id
   * 
   * @MongoDB\Int
   */
  private $broadcast_type_id;

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
   * @var BroadcastType $a_broadcast_type
   * 
   * @MongoDB\ReferenceOne(targetDocument="BroadcastType")
   */
  private $a_broadcast_type;

  /** 
   * @var string $description
   * 
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

  /** 
   * @var locale $locale
   */
  private $locale;

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
   * @param int $broadcast_type_id
   */
  public function setBroadcastTypeId($broadcast_type_id)
  {
    $this->broadcast_type_id = $broadcast_type_id;
  }
  
  /**
   * Get broadcast_type_id
   *
   * @return int
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
   * Set a_broadcast_type
   *
   * @param string $a_broadcast_type
   */
  public function setABroadcastType($a_broadcast_type)
  {
    $this->a_broadcast_type = $a_broadcast_type;
  }
  
  /**
   * Get a broadcast type
   *
   * @return string
   */
  public function getABroadcastType()
  {
    return $this->a_broadcast_type;
  } 

  /**
   * Set description
   *
   * @param string $description
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
   * @return string
   */
  public function getDescription($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->description[$locale])){
      return null;
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
   * Clone Direct
   *
   * @return Direct
   */
  public function cloneResource()
  {
    $aux = clone $this;
    $aux->id = null;

    return $aux;
  }

}