<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedSocial
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $twitter;

    /**
     * @MongoDB\Field(type="string")
     */
    private $twitterHashtag;

    /**
     * @MongoDB\Field(type="string")
     */
    private $email;

    public function __toString(): string
    {
        return $this->getTwitterHashtag() ?? '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTwitter($twitter): void
    {
        $this->twitter = $twitter;
    }

    public function getTwitter()
    {
        return $this->twitter;
    }

    public function setTwitterHashtag($twitterHashtag): void
    {
        $this->twitterHashtag = $twitterHashtag;
    }

    public function getTwitterHashtag()
    {
        return $this->twitterHashtag;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }
}
