<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Pumukit\SchemaBundle\Document\ElementInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\MappedSuperclass
 */
class Element implements ElementInterface
{
    use Traits\Properties;

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="collection")
     */
    private $tags = [];

    /**
     * @MongoDB\Field(type="string")
     */
    private $url;

    /**
     * @MongoDB\Field(type="string")
     */
    private $path;

    /**
     * @MongoDB\Field(type="string")
     */
    private $mime_type;

    /**
     * @MongoDB\Field(type="int")
     */
    private $size;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $hide = false;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    private $locale = 'en';

    public function __construct() {}

    public function __clone()
    {
        $this->id = null;
    }

    public function __toString(): string
    {
        return $this->getUrl();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function addTag(string $tag): array
    {
        $this->tags[] = $tag;

        return $this->tags = array_unique($this->tags);
    }

    public function removeTag($tag): bool
    {
        $tag = array_search($tag, $this->tags, true);

        if (false !== $tag) {
            unset($this->tags[$tag]);

            return true;
        }

        return false;
    }

    public function containsTag($tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function containsAllTags(array $tags): bool
    {
        return count(array_intersect($tags, $this->tags)) === count($tags);
    }

    public function containsAnyTag(array $tags): bool
    {
        return 0 !== count(array_intersect($tags, $this->tags));
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setMimeType(string $mime_type): void
    {
        $this->mime_type = $mime_type;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function getHide(): bool
    {
        return $this->hide;
    }

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function setDescription(?string $description, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    public function getDescription($locale = null): ?string
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

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
