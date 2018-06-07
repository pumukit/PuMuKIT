<?php

namespace Pumukit\LiveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\LiveBundle\Repository\MessageRepository")
 */
class Message
{
    /**
     * @var int id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $author;

    /**
     * @var string multimediaObject
     * @MongoDB\ReferenceOne(targetDocument="Pumukit\SchemaBundle\Document\MultimediaObject", inversedBy="multimedia_object", simple=true)
     * @MongoDB\Index
     */
    private $multimediaObject;

    /**
     * @var string message
     *
     * @MongoDB\String
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @MongoDB\Date
     */
    private $insertDate;

    /**
     * @var string cookie
     *
     * @MongoDB\String
     */
    private $cookie;

    /**
     * Get id.
     *
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $content
     */
    public function setMessage($content)
    {
        $this->message = $content;
    }

    /**
     * @param \DateTime $insertDate
     */
    public function setInsertDate($insertDate)
    {
        $this->insertDate = $insertDate;
    }

    /**
     * @return \DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * @param string $multimediaObject
     */
    public function setMultimediaObject($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;
    }

    /**
     * @return string
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @param string $cookie
     */
    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
    }
}
