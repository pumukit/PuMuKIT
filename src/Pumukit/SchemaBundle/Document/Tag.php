<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 *
 * @Gedmo\Tree(type="materializedPath", activateLocking=false)
 */
class Tag implements TagInterface
{
    use Traits\Properties;
    use Traits\Tag;

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="int", strategy="increment" )
     */
    private $number_multimedia_objects = 0;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $label = ['en' => ''];

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
     *
     * @MongoDB\UniqueIndex(order="asc")
     *
     * @Assert\Regex("/^\w*$/")
     *
     * @Gedmo\TreePathSource
     */
    private $cod = '';

    /**
     * @MongoDB\Field(type="bool")
     */
    private $metatag = false;

    /**
     * @MongoDB\Field(type="bool")
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
     * @Gedmo\TreeParent
     *
     * @MongoDB\ReferenceOne(targetDocument=Tag::class, inversedBy="children", cascade={"persist"})
     *
     * @MongoDB\Index
     */
    private $parent;

    /**
     * @MongoDB\ReferenceMany(targetDocument=Tag::class, mappedBy="parent", sort={"cod": 1})
     */
    private $children = [];

    /**
     * @MongoDB\Field(type="int", strategy="increment" )
     */
    private $number_children = 0;

    /**
     * @MongoDB\Field(type="string")
     *
     * @Gedmo\TreePath(separator="|", appendId=false, startsWithSeparator=false, endsWithSeparator=true)
     */
    private $path;

    /**
     * @Gedmo\TreeLevel
     *
     * @MongoDB\Field(type="int")
     */
    private $level;

    /**
     * @Gedmo\TreeLockTime
     *
     * @MongoDB\Field(type="date")
     */
    private $lockTime;

    public function __construct() {}

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

    public function setLabel(string $label, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->label[$locale] = $label;
    }

    public function getLabel(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->label[$locale]) || '' === $this->label[$locale]) {
            return $this->getTitle($locale);
        }

        return $this->label[$locale];
    }

    public function getI18nTitle(): ?array
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

    public function increaseNumberMultimediaObjects(): void
    {
        ++$this->number_multimedia_objects;
    }

    public function decreaseNumberMultimediaObjects(): void
    {
        --$this->number_multimedia_objects;
    }

    public function getNumberMultimediaObjects(): int
    {
        return $this->number_multimedia_objects;
    }

    public function setNumberMultimediaObjects($count): void
    {
        $this->number_multimedia_objects = $count;
    }

    public function setParent(TagInterface $parent = null): void
    {
        $this->parent = $parent;

        $parent->addChild($this);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getNumberOfChildren(): ?int
    {
        return $this->number_children;
    }

    public function setNumberOfChildren($count): void
    {
        $this->number_children = $count;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }

    private function addChild(self $tag): TagInterface
    {
        ++$this->number_children;

        return $this->children[] = $tag;
    }
}
