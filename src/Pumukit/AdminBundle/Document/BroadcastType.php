<?php

namespace Pumukit\AdminBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Pumukit\AdminBundle\Document\BroadcastType
 *
 * @MongoDB\Document(repositoryClass="Pumukit\AdminBundle\Repository\BroadcastTypeRepository")
 */
class BroadcastType
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
   * @var boolean $default_sel
   * 
   * @MongoDB\Boolean
   */
  private $default_sel = false;

  /**
   * To String function
   */
  public function __toString()
  {
    return $this->name;
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

}