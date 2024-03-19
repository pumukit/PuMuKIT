<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedEvent
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $author;

    /**
     * @MongoDB\Field(type="string")
     */
    private $producer;

    /**
     * @MongoDB\Field(type="string")
     */
    private $place;

    /**
     * @MongoDB\Field(type="date")
     */
    private $date;

    /**
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $display = true;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $create_serial = true;

    /**
     * @MongoDB\EmbedMany(targetDocument=EmbeddedEventSession::class)
     */
    private $embeddedEventSession;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Live::class, cascade={"persist"})
     */
    private $live;

    /**
     * @MongoDB\Field(type="string")
     *
     * @Assert\NotBlank()
     *
     * @Assert\Url(protocols= {"rtmpt", "rtmp", "http", "mms", "rtp", "https"})
     */
    private $url;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $isIframeUrl = false;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $alreadyHeldMessage = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $notYetHeldMessage = ['en' => ''];

    /**
     * @MongoDB\Field(type="bool")
     */
    private $enableChat = false;

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->embeddedEventSession = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    public function getName(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->name[$locale] ?? '';
    }

    public function setI18nName(array $name): void
    {
        $this->name = $name;
    }

    public function getI18nName(): array
    {
        return $this->name;
    }

    public function getDescription(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->description[$locale] ?? '';
    }

    public function setDescription(string $description, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        $this->description[$locale] = $description;
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function getPlace()
    {
        return $this->place;
    }

    public function setPlace($place): void
    {
        $this->place = $place;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    public function getProducer()
    {
        return $this->producer;
    }

    public function setProducer(string $producer): void
    {
        $this->producer = $producer;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function setDisplay(bool $display): void
    {
        $this->display = $display;
    }

    public function isCreateSerial(): bool
    {
        return $this->create_serial;
    }

    public function setCreateSerial(bool $create_serial): void
    {
        $this->create_serial = $create_serial;
    }

    public function getEmbeddedEventSession(): array
    {
        $embeddedEventSession = $this->embeddedEventSession->toArray();
        usort($embeddedEventSession, static function (EmbeddedEventSession $a, EmbeddedEventSession $b) {
            return $a->getStart() > $b->getStart();
        });

        return $embeddedEventSession;
    }

    public function setEmbeddedEventSession($embeddedEventSession): void
    {
        $this->embeddedEventSession = $embeddedEventSession;
    }

    public function addEmbeddedEventSession($embeddedEventSession): void
    {
        $this->embeddedEventSession->add($embeddedEventSession);
    }

    public function removeEmbeddedEventSession(EmbeddedEventSession $embeddedEventSession): bool
    {
        foreach ($this->embeddedEventSession as $session) {
            if ($session->getId() === $embeddedEventSession->getId()) {
                $removed = $this->embeddedEventSession->removeElement($embeddedEventSession);
                $this->embeddedEventSession = new ArrayCollection(array_values($this->embeddedEventSession->toArray()));

                return $removed;
            }
        }

        return false;
    }

    public function getLive(): ?Live
    {
        return $this->live;
    }

    public function setLive(Live $live): void
    {
        $this->live = $live;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return bool
     */
    public function isIframeUrl()
    {
        return $this->isIframeUrl;
    }

    /**
     * @return bool
     */
    public function getIsIframeUrl()
    {
        return $this->isIframeUrl;
    }

    /**
     * @param bool $isIframeUrl
     */
    public function setIsIframeUrl($isIframeUrl)
    {
        $this->isIframeUrl = $isIframeUrl;
    }

    /**
     * Set already held message.
     *
     * @param string     $message
     * @param mixed|null $locale
     */
    public function setAlreadyHeldMessage($message, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->alreadyHeldMessage[$locale] = $message;
    }

    public function getAlreadyHeldMessage(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->alreadyHeldMessage[$locale] ?? '';
    }

    public function setI18nAlreadyHeldMessage(array $message): void
    {
        $this->alreadyHeldMessage = $message;
    }

    public function getI18nAlreadyHeldMessage(): array
    {
        return $this->alreadyHeldMessage;
    }

    public function setNotYetHeldMessage(string $message, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->notYetHeldMessage[$locale] = $message;
    }

    public function getNotYetHeldMessage(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->notYetHeldMessage[$locale] ?? '';
    }

    public function setI18nNotYetHeldMessage(array $message): void
    {
        $this->notYetHeldMessage = $message;
    }

    public function getI18nNotYetHeldMessage(): array
    {
        return $this->notYetHeldMessage;
    }

    public function setEnableChat(bool $enableChat): void
    {
        $this->enableChat = $enableChat;
    }

    public function getEnableChat(): bool
    {
        return $this->enableChat;
    }

    public function isChatEnabled(): bool
    {
        return $this->enableChat;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
