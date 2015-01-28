<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Document\User as BaseUser;

/**
 * Pumukit\SchemaBundle\Document\User
 *
 * @MongoDB\Document
 */
class User extends BaseUser
{
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
}
