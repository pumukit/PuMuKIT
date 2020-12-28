<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MessageRepository")
 */
class Message
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $author;

    /**
     * @MongoDB\ReferenceOne(targetDocument=MultimediaObject::class, inversedBy="multimedia_object", storeAs="id", cascade={"persist"})
     * @MongoDB\Index
     */
    private $multimediaObject;

    /**
     * @MongoDB\Field(type="string")
     */
    private $channel;

    /**
     * @MongoDB\Field(type="string")
     */
    private $message;

    /**
     * @MongoDB\Field(type="date")
     */
    private $insertDate;

    /**
     * @MongoDB\Field(type="string")
     */
    private $cookie;

    public function getId()
    {
        return $this->id;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setMessage($content): void
    {
        $this->message = $content;
    }

    public function setInsertDate($insertDate): void
    {
        $this->insertDate = $insertDate;
    }

    public function getInsertDate()
    {
        return $this->insertDate;
    }

    public function setMultimediaObject($multimediaObject): void
    {
        $this->multimediaObject = $multimediaObject;
    }

    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    public function getCookie()
    {
        return $this->cookie;
    }

    public function setCookie($cookie): void
    {
        $this->cookie = $cookie;
    }
}
