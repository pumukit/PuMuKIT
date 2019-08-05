<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Live as DocumentLive;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedEvent.
 *
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedEvent
{
    /**
     * @var int
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $name;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $description;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $author;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $producer;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $place;

    /**
     * @var \DateTime
     * @MongoDB\Field(type="date")
     */
    private $date;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $duration;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $display = true;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $create_serial = true;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="EmbeddedEventSession")
     */
    private $embeddedEventSession;

    /**
     * @var DocumentLive
     * @MongoDB\ReferenceOne(targetDocument="Pumukit\SchemaBundle\Document\Live", cascade={"persist"})
     */
    private $live;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url(protocols= {"rtmpt", "rtmp", "http", "mms", "rtp", "https"})
     */
    private $url;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $alreadyHeldMessage = ['en' => ''];

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $notYetHeldMessage = ['en' => ''];

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $enableChat = false;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var string
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->duration = 0;
        $this->embeddedEventSession = new ArrayCollection();
        $this->name = ['en' => ''];
        $this->description = ['en' => ''];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string      $name
     * @param null|string $locale
     */
    public function setName($name, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    /**
     * Get name.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get I18n name.
     *
     * @return array
     */
    public function getI18nName()
    {
        return $this->name;
    }

    /**
     * @param null|string $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * @param string $description
     * @param string $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        $this->description[$locale] = $description;
    }

    /**
     * Set I18n name.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get I18n description.
     *
     * @return array
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param string $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
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
    public function getProducer()
    {
        return $this->producer;
    }

    /**
     * @param string $producer
     */
    public function setProducer($producer)
    {
        $this->producer = $producer;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return bool
     */
    public function isDisplay()
    {
        return $this->display;
    }

    /**
     * @param bool $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * @return bool
     */
    public function isCreateSerial()
    {
        return $this->create_serial;
    }

    /**
     * @param bool $create_serial
     */
    public function setCreateSerial($create_serial)
    {
        $this->create_serial = $create_serial;
    }

    /**
     * @return ArrayCollection
     */
    public function getEmbeddedEventSession()
    {
        $embeddedEventSession = $this->embeddedEventSession->toArray();
        usort($embeddedEventSession, function ($a, $b) {
            return $a->getStart() > $b->getStart();
        });

        return $embeddedEventSession;
    }

    /**
     * @param ArrayCollection $embeddedEventSession
     */
    public function setEmbeddedEventSession($embeddedEventSession)
    {
        $this->embeddedEventSession = $embeddedEventSession;
    }

    /**
     * @param EmbeddedEventSession $embeddedEventSession
     *
     * @return mixed
     */
    public function addEmbeddedEventSession($embeddedEventSession)
    {
        return $this->embeddedEventSession->add($embeddedEventSession);
    }

    /**
     * @param EmbeddedEventSession $embeddedEventSession
     *
     * @return bool
     */
    public function removeEmbeddedEventSession($embeddedEventSession)
    {
        foreach ($this->embeddedEventSession as $session) {
            if ($session->getId() == $embeddedEventSession->getId()) {
                $removed = $this->embeddedEventSession->removeElement($embeddedEventSession);
                $this->embeddedEventSession = new ArrayCollection(array_values($this->embeddedEventSession->toArray()));

                return $removed;
            }
        }

        return false;
    }

    /**
     * @return DocumentLive
     */
    public function getLive()
    {
        return $this->live;
    }

    /**
     * @param DocumentLive $live
     */
    public function setLive($live)
    {
        $this->live = $live;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set already held message.
     *
     * @param string     $message
     * @param null|mixed $locale
     */
    public function setAlreadyHeldMessage($message, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->alreadyHeldMessage[$locale] = $message;
    }

    /**
     * Get Already Held Message.
     *
     * @param null|mixed $locale
     *
     * @return string
     */
    public function getAlreadyHeldMessage($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->alreadyHeldMessage[$locale])) {
            return '';
        }

        return $this->alreadyHeldMessage[$locale];
    }

    /**
     * Set I18n Already Held Message.
     *
     * @param array $message
     */
    public function setI18nAlreadyHeldMessage(array $message)
    {
        $this->alreadyHeldMessage = $message;
    }

    /**
     * Get I18n Already Held Message.
     *
     * @return array
     */
    public function getI18nAlreadyHeldMessage()
    {
        return $this->alreadyHeldMessage;
    }

    /**
     * Set Not Yet held message.
     *
     * @param string     $message
     * @param null|mixed $locale
     */
    public function setNotYetHeldMessage($message, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->notYetHeldMessage[$locale] = $message;
    }

    /**
     * Get Not Yet Held Message.
     *
     * @param null|mixed $locale
     *
     * @return string
     */
    public function getNotYetHeldMessage($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->notYetHeldMessage[$locale])) {
            return '';
        }

        return $this->notYetHeldMessage[$locale];
    }

    /**
     * Set I18n Not Yet Held Message.
     *
     * @param array $message
     */
    public function setI18nNotYetHeldMessage(array $message)
    {
        $this->notYetHeldMessage = $message;
    }

    /**
     * Get I18n Not Yet Held Message.
     *
     * @return array
     */
    public function getI18nNotYetHeldMessage()
    {
        return $this->notYetHeldMessage;
    }

    /**
     * Set enableChat.
     *
     * @param bool $enableChat
     */
    public function setEnableChat($enableChat)
    {
        $this->enableChat = $enableChat;
    }

    /**
     * Get enableChat.
     *
     * @return bool
     */
    public function getEnableChat()
    {
        return $this->enableChat;
    }

    /**
     * Is chat enabled.
     *
     * @return bool
     */
    public function isChatEnabled()
    {
        return $this->enableChat;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
