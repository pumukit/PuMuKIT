<?php

namespace Pumukit\AdminBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Pumukit\AdminBundle\Document\Broadcast
 *
 * @MongoDB\Document
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
   * @MongoDB\Raw
   */
  private $name = array('en' => '');

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