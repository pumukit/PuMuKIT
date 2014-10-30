<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Document\User as BaseUser;

/**
 * Pumukit\SchemaBundle\Document\User
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
  const USER_TYPE_ADMIN = 'Administrator';
  const USER_TYPE_PUB = 'Publisher';
  const USER_TYPE_FTP = 'FTP';
  
  /**
   * @var int $id
   *
   * @MongoDB\Id(strategy="auto")
   */
  protected $id;

  /**
   * @var string $fullname
   *
   * @MongoDB\String
   */
  protected $fullname;

  /**
   * @var string $type
   *
   * @MongoDB\Raw
   */
  protected $user_type = array('en'=>self::USER_TYPE_ADMIN);

  /**
   * @var boolean $root
   *
   * @MongoDB\Boolean
   */
  protected $root;  

  /**
   * @var locale $locale
   */
  private $locale = 'en';
  
  public function __construct()
  {
    parent::__construct();
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

  /**
   * Set user_type
   *
   * @param string $user_type
   */
  public function setUserType($user_type, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->user_type[$locale] = $user_type;
  }
  
  /**
   * Get user_type
   *
   * @return string
   */
  public function getUserType($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->user_type[$locale])){
      return null;
    }
    return $this->user_type[$locale];
  }

  /**
   * Set I18n user_type
   *
   * @param array $user_type
   */
  public function setI18nUserType(array $user_type)
  {
    $this->user_type = $user_type;
  }
  
  /**
   * Get i18n user_type
   *
   * @return array
   */
  public function getI18nUserType()
  {
    return $this->user_type;
  }

  /**
   * Set root
   *
   * @param boolean $root
   */
  public function setRoot($root)
  {
    $this->root = $root;
  }
  
  /**
   * Get root
   *
   * @return boolean
   */
  public function getRoot()
  {
    return $this->root;
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

}