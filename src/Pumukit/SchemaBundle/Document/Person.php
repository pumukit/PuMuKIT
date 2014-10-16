<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\ArrayCollection;

use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;

/**
 * Pumukit\SchemaBundle\Document\Person
 *
 * @MongoDB\EmbeddedDocument
 */
class Person
{
  /**
   * @var int $id
   *
   * @MongoDB\Id
   */
  protected $id;

  /**
   * @var string $name
   *
   * @MongoDB\String
   */
  protected $name;

  /**
   * @var string $email
   *
   * @MongoDB\String
   */
  protected $email;

  /**
   * @var string $web
   *
   * @MongoDB\String
   * //@Assert\Url()
   */
  protected $web;

  /**
   * @var string $phone
   *
   * @MongoDB\String
   */
  protected $phone;

  /**
   * @var string $honorific
   *
   * @MongoDB\Raw
   */
  protected $honorific = array('en'=>'');

  /**
   * @var string $firm
   *
   * @MongoDB\Raw
   */
  protected $firm = array('en'=>'');

  /**
   * @var string $post
   *
   * @MongoDB\Raw
   */
  protected $post = array('en'=>'');

  /**
   * @var string $bio
   *
   * @MongoDB\Raw
   */
  protected $bio = array('en'=>'');

  /**
   * @var ArrayCollection $person_in_multimedia_object
   *
   * @MongoDB\EmbedMany(targetDocument="PersonInMultimediaObject")
   */
  protected $people_in_multimedia_object;

  /**
   * Locale
   * @var locale $locale
   */
  protected $locale = 'en';

  /**
   * Get id
   *
   * @return integer
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
   * Set email
   *
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }

  /**
   * Get email
   *
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  } 

  /**
   * Set web
   *
   * @param string $web
   */
  public function setWeb($web)
  {
    $this->web = $web;
  }

  /**
   * Get web
   *
   * @return string
   */
  public function getWeb()
  {
    return $this->web;
  }

  /**
   * Set phone
   *
   * @param string $phone
   */
  public function setPhone($phone)
  {
    $this->phone = $phone;
  }

  /**
   * Get phone
   *
   * @return string
   */
  public function getPhone()
  {
    return $this->phone;
  }

  /**
   * Set honorific
   *
   * @param string $honorific
   */
  public function setHonorific($honorific, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->honorific[$locale] = $honorific;
  }

  /**
   * Get honorific
   *
   * @return string
   */
  public function getHonorific($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->honorific[$locale])){
      return null;
    }
    return $this->honorific[$locale];
  }

  /**
   * Set firm
   *
   * @param string $firm
   */
  public function setFirm($firm, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->firm[$locale] = $firm;
  }

  /**
   * Get firm
   *
   * @return string
   */
  public function getFirm($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->firm[$locale])){
      return null;
    }
    return $this->firm[$locale];
  }

  /**
   * Set post
   *
   * @param string $post
   */
  public function setPost($post, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->post[$locale] = $post;
  }

  /**
   * Get post
   *
   * @return string
   */
  public function getPost($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->post[$locale])){
      return null;
    }
    return $this->post[$locale];
  }

  /**
   * Set bio
   *
   * @param string $bio
   */
  public function setBio($bio, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->bio[$locale] = $bio;
   }

  /**
   * Get bio
   *
   * @return string
   */
  public function getBio($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->bio[$locale])){
      return null;
    }
    return $this->bio[$locale];
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
    //parent::__construct();
  }

  /**
   * Add people_in_multimedia_object
   *
   * @param PersonInMultimediaObject $peopleInMultimediaObject
   * @return Person
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
}
