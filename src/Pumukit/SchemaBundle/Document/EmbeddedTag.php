<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedTag implements TagInterface
{
    use Traits\Tag;

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $slug;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $cod = '';

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $metatag = false;

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $display = false;

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    private $locale = 'en';

    /**
     * @MongoDB\Field(type="date")
     */
    private $created;

    /**
     * @MongoDB\Field(type="date")
     */
    private $updated;

    /**
     * @MongoDB\Field(type="string")
     */
    private $path;

    /**
     * @MongoDB\Field(type="int")
     */
    private $level;

    public function __construct(TagInterface $tag)
    {
        if (null !== $tag) {
            $this->id = $tag->getId();
            $this->setI18nTitle($tag->getI18nTitle());
            $this->setI18nDescription($tag->getI18nDescription());
            $this->slug = $tag->getSlug();
            $this->cod = $tag->getCod();
            $this->metatag = $tag->getMetatag();
            $this->display = $tag->isDisplay();
            $this->locale = $tag->getLocale();
            $this->created = $tag->getCreated();
            $this->updated = $tag->getUpdated();
            $this->path = $tag->getPath();
            $this->level = $tag->getLevel();
        }
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(string $title, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    public function getTitle(string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->title[$locale] ?? '';
    }

    public function getI18nTitle(): array
    {
        return $this->title;
    }

    public function setI18nTitle(array $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    public function getDescription(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->description[$locale] ?? '';
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): ?array
    {
        return $this->description;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setCod(string $code): void
    {
        $this->cod = $code;
    }

    public function getCod(): string
    {
        return $this->cod;
    }

    public function setMetatag(bool $metaTag): void
    {
        $this->metatag = $metaTag;
    }

    public function getMetatag(): ?bool
    {
        return $this->metatag;
    }

    public function setDisplay(bool $display): void
    {
        $this->display = $display;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setUpdated(\DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public static function getEmbeddedTag($embedTags, $tag): EmbeddedTag
    {
        if ($tag instanceof self) {
            return $tag;
        }

        if ($tag instanceof Tag) {
            return new self($tag);
        }

        throw new \InvalidArgumentException('Only Tag or EmbeddedTag are allowed.');
    }
}
