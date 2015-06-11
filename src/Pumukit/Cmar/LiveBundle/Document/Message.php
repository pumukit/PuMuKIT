<?php

namespace Pumukit\Cmar\LiveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\Cmar\LiveBundle\Repository\MessageRepository")
 */
class Message
{
    /**
     * @var integer id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string $author
     *
     * @MongoDB\String
     */
    private $author;

    /**
     * @var string channel
     *
     * @MongoDB\String
     */
    private $channel;

    /**
     * @var string message
     *
     * @MongoDB\String
     */
    private $message;

    /**
     * @var \DateTime $insertDate
     *
     * @MongoDB\Date
     */
    private $insertDate;

    /**
     * Get id
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
     * @param string $channel
     */
    public function setChannel($channel)
    {
      $this->channel = $channel;
    }
}