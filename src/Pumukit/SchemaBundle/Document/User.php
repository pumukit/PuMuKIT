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
  /**
   * @var int $id
   *
   * @MongoDB\Id(strategy="auto")
   */
  protected $id;

  public function __construct()
  {
    parent::__construct();
  }
}