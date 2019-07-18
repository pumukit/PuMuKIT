<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MessageRepository")
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
     * @MongoDB\Field(type="string")
     */
    private $author;

    /**
     * @var string multimediaObject
     * @MongoDB\ReferenceOne(targetDocument="Pumukit\SchemaBundle\Document\MultimediaObject", inversedBy="multimedia_object", storeAs="id", cascade={"persist"})
     * @MongoDB\Index
     */
    private $multimediaObject;

    /**
     * @var string channel
     *
     * @MongoDB\Field(type="string")
     */
    private $channel;

    /**
     * @var string message
     *
     * @MongoDB\Field(type="string")
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $insertDate;

    /**
     * @var string cookie
     *
     * @MongoDB\Field(type="string")
     */
    private $cookie;

    /**
     * Get id.
     *
     * @return string
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
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
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
