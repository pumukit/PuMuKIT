<?php

namespace Pumukit\SchemaBundle\Document;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Pumukit\SchemaBundle\Document\Tag.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\TagRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=false)
 */
class Tag
{
    use Traits\Properties;

    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * Number of Multimedia Object with this tag. Only for cache purposes.
     *
     * @var int
     *
     * @MongoDB\Field(type="int")
     * @MongoDB\Increment
     */
    private $number_multimedia_objects = 0;

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $label = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $slug;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex(order="asc")
     * @Assert\Regex("/^\w*$/")
     * @Gedmo\TreePathSource
     */
    private $cod = '';

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $metatag = false;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $display = false;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var string
     */
    private $locale = 'en';

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $updated;

    /**
     * @Gedmo\TreeParent
     * @MongoDB\ReferenceOne(targetDocument="Tag", inversedBy="children", cascade={"persist"})
     * @MongoDB\Index
     */
    private $parent;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Tag", mappedBy="parent", sort={"cod": 1})
     */
    private $children = [];

    /**
     * Number of children. Only for cache purposes.
     *
     * @var int
     *
     * @MongoDB\Field(type="int")
     * @MongoDB\Increment
     */
    private $number_children = 0;

    /**
     * @MongoDB\Field(type="string")
     * @Gedmo\TreePath(separator="|", appendId=false, startsWithSeparator=false, endsWithSeparator=true)
     */
    private $path;

    /**
     * @Gedmo\TreeLevel
     * @MongoDB\Field(type="int")
     */
    private $level;

    /**
     * @Gedmo\TreeLockTime
     * @MongoDB\Field(type="date")
     */
    private $lockTime;

    public function __construct()
    {
        $this->children = [];
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
     * Set title.
     *
     * @param string      $title
     * @param string|null $locale
     */
    public function setTitle($title, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    /**
     * Get title.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->title[$locale])) {
            return '';
        }

        return $this->title[$locale];
    }

    /**
     * Set label.
     *
     * @param string      $label
     * @param string|null $locale
     */
    public function setLabel($label, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->label[$locale] = $label;
    }

    /**
     * Get label.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getLabel($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->label[$locale])) {
            return $this->getTitle($locale);
        }

        return $this->label[$locale];
    }

    /**
     * Get i18n title.
     *
     * @return array
     */
    public function getI18nTitle()
    {
        return $this->title;
    }

    /**
     * Set i18n title.
     *
     * @param array $title
     */
    public function setI18nTitle(array $title)
    {
        $this->title = $title;
    }

    /**
     * Set description.
     *
     * @param string      $description
     * @param string|null $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param string|null $locale
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
     * Set i18n description.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get i18n description.
     *
     * @return array
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return Tag
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set cod.
     *
     * @param string $cod
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
    }

    /**
     * Get cod.
     *
     * @return string
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * Set metatag.
     *
     * @param bool $metatag
     */
    public function setMetatag($metatag)
    {
        $this->metatag = $metatag;
    }

    /**
     * Get metatag.
     *
     * @return bool
     */
    public function getMetatag()
    {
        return $this->metatag;
    }

    /**
     * Set display.
     *
     * @param bool $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * Get display.
     *
     * @return bool
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set translatable locale.
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

    /**
     * to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * Increase number_multimedia_objects.
     */
    public function increaseNumberMultimediaObjects()
    {
        ++$this->number_multimedia_objects;
    }

    /**
     * Decrease number_multimedia_objects.
     */
    public function decreaseNumberMultimediaObjects()
    {
        --$this->number_multimedia_objects;
    }

    /**
     * Get number_multimedia_objects.
     */
    public function getNumberMultimediaObjects()
    {
        return $this->number_multimedia_objects;
    }

    /**
     * Set number_multimedia_objects.
     */
    public function setNumberMultimediaObjects($count)
    {
        return $this->number_multimedia_objects = $count;
    }

    public function setParent(Tag $parent = null)
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

    private function addChild(Tag $tag)
    {
        ++$this->number_children;

        return $this->children[] = $tag;
    }

    public function getNumberOfChildren()
    {
        return $this->number_children;
    }

    public function setNumberOfChildren($count)
    {
        $this->number_children = $count;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * Returns true if given node is children of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isChildOf($tag)
    {
        if ($this->isDescendantOf($tag)) {
            $suffixPath = substr($this->getPath(), strlen($tag->getPath()), strlen($this->getPath()));
            if (1 === substr_count($suffixPath, '|')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if given node is descendant of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isDescendantOf($tag)
    {
        if ($tag == $this) {
            return false;
        }

        return substr($this->getPath(), 0, strlen($tag->getPath())) === $tag->getPath();
    }

    /**
     * Returns true if given node is descendant of tag or the same.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function equalsOrDescendantOf($tag)
    {
        return substr($this->getPath(), 0, strlen($tag->getPath())) === $tag->getPath();
    }

    /**
     * Returns true if given node cod is descendant of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isDescendantOfByCod($tagCod)
    {
        if ($tagCod == $this->getCod()) {
            return false;
        }
        if (0 === strpos($this->getPath(), sprintf('%s|', $tagCod))) {
            return true;
        }

        return false === strpos($this->getPath(), sprintf('|%s|', $tagCod)) ? false : true;
    }

    /**
     * Returns true if the tag is a PUB tag (that appears in the Pub tab in the back-office).
     */
    public function isPubTag()
    {
        return $this->isDescendantOfByCod('PUBCHANNELS') || $this->isDescendantOfByCod('PUBDECISIONS');
    }
}
