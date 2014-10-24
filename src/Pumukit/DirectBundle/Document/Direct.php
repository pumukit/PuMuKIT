<?php

namespace Pumukit\DirectBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Pumukit\DirectBundle\Document\Direct
 *
 * @MongoDB\Document(repositoryClass="Pumukit\DirectBundle\Repository\DirectRepository")
 */
class Direct
{
  const DIRECT_TYPE_FMS = 'FMS';
  const DIRECT_TYPE_WMS = 'WMS';

  /** 
   * @var int $id
   * 
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var string $url
   * 
   * @MongoDB\String
   * @Assert\NotBlank()
   * @Assert\Url(protocols= {"rtmp", "http", "mms", "rtp", "https"})
   */
  private $url;

  /**
   * @var string $passwd
   *
   * @MongoDB\String
   */
  private $passwd;
  
  /**
   * @var int $direct_type
   *
   * @MongoDB\String
   */
  private $direct_type = self::DIRECT_TYPE_FMS;
  
  /**
   * @var int $resolution_width
   *
   * @MongoDB\Int
   */
  private $resolution_width = 0;

  /**
   * @var int $resolution_height
   *
   * @MongoDB\Int
   */
  private $resolution_height = 0;

  /**
   * @var string $qualities
   *
   * @MongoDB\Raw
   */
  private $qualities;

  /**
   * @var string $ip_source
   *
   * @MongoDB\String
   * @Assert\Ip
   */
  private $ip_source;

  /**
   * @var string $source_name
   *
   * @MongoDB\String
   * @Assert\NotBlank()
   */
  private $source_name;
   
  /**
   * @var boolean $index_play
   * 
   * @MongoDB\Boolean
   */
  private $index_play = false;

  /**
   * @var boolean $broadcasting
   *
   * @MongoDB\Boolean
   */
  private $broadcasting = false;

  /**
   * @var boolean $debug
   *
   * @MongoDB\Boolean
   */
  private $debug = false;

  /**
   * @var string $name
   * 
   * @MongoDB\Raw
   * @Assert\NotBlank()
   */
  private $name = array('en'=>'');

  /**
   * @var string $description
   *
   * @MongoDB\Raw
   */
  private $description = array('en'=>'');
  
  /**
   * @var locale $locale
   */
  private $locale = 'en';

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
   * Set url
   *
   * @param string $url
   */
  public function setUrl($url)
  {
    $this->url = $url;
  }
  
  /**
   * Get url
   *
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
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
   * Set direct_type
   *
   * @param string $direct_type
   */
  public function setDirectType($direct_type)
  {
    $this->direct_type = $direct_type;
  }
  
  /**
   * Get direct_type
   *
   * @return string
   */
  public function getDirectType()
  {
    return $this->direct_type;
  }

  /**
   * @Assert\True(message = "Direct type not valid")
   */
  public function isValidDirectType()
  {
    return in_array($this->direct_type, array(self::DIRECT_TYPE_WMS, self::DIRECT_TYPE_FMS));
  }

  /**
   * Set resolution_width
   *
   * @param int $resolution_width
   */
  public function setResolutionWidth($resolution_width)
  {
    $this->resolution_width = $resolution_width;
  }
  
  /**
   * Get resolution_width
   *
   * @return int
   */
  public function getResolutionWidth()
  {
    return $this->resolution_width;
  }

  /**
   * Set resolution_height
   *
   * @param int $resolution_height
   */
  public function setResolutionHeight($resolution_height)
  {
    $this->resolution_height = $resolution_height;
  }
  
  /**
   * Get resolution_height
   *
   * @return int
   */
  public function getResolutionHeight()
  {
    return $this->resolution_height;
  } 

  /**
   * Set qualities
   *
   * @param string $qualities
   */
  public function setQualities($qualities)
  {
    $this->qualities = $qualities;
  }
  
  /**
   * Get qualities
   *
   * @return string
   */
  public function getQualities()
  {
    return $this->qualities;
  }  

  /**
   * Set ip_source
   *
   * @param string $ip_source
   */
  public function setIpSource($ip_source)
  {
    $this->ip_source = $ip_source;
  }
  
  /**
   * Get ip_source
   *
   * @return string
   */
  public function getIpSource()
  {
    return $this->ip_source;
  }

  /**
   * Set source_name
   *
   * @param string $source_name
   */
  public function setSourceName($source_name)
  {
    $this->source_name = $source_name;
  }
  
  /**
   * Get source_name
   *
   * @return string
   */
  public function getSourceName()
  {
    return $this->source_name;
  }

  /**
   * Set index_play
   *
   * @param boolean $index_play
   */
  public function setIndexPlay($index_play)
  {
    $this->index_play = $index_play;
  }
  
  /**
   * Get index_play
   *
   * @return boolean
   */
  public function getIndexPlay()
  {
    return $this->index_play;
  }

  /**
   * Set broadcasting
   *
   * @param boolean $broadcasting
   */
  public function setBroadcasting($broadcasting)
  {
    $this->broadcasting = $broadcasting;
  }
  
  /**
   * Get broadcasting
   *
   * @return boolean
   */
  public function getBroadcasting()
  {
    return $this->broadcasting;
  }

  /**
   * Set debug
   *
   * @param boolean $debug
   */
  public function setDebug($debug)
  {
    $this->debug = $debug;
  }
  
  /**
   * Get debug
   *
   * @return boolean
   */
  public function getDebug()
  {
    return $this->debug;
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
   * Get I18n description
   *
   * @return string
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

  

}