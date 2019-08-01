<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedSocial.
 *
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedSocial
{
    /**
     * @var int
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $twitter;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $twitterHashtag;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $email;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getTwitterHashtag();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set twitter widget id.
     *
     * @param string $twitter
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * Get twitter widget id.
     *
     * @return string
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * Set twitter hashtag.
     *
     * @param mixed $twitterHashtag
     */
    public function setTwitterHashtag($twitterHashtag)
    {
        $this->twitterHashtag = $twitterHashtag;
    }

    /**
     * Get twitterHashtag.
     *
     * @return string
     */
    public function getTwitterHashtag()
    {
        return $this->twitterHashtag;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
