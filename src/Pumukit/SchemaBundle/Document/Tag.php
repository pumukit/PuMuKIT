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
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var int
     *
     * @MongoDB\Int
     * @MongoDB\Increment
     */
    private $number_multimedia_objects = 0;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $title = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $description = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $slug;

    /**
     * @var string
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(order="asc")
     * @Assert\Regex("/^\w*$/")
     * @Gedmo\TreePathSource
     *
     * TODO Unique Index #6098
     */
    private $cod = '';

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $metatag = false;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $display = false;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $created;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $updated;

    /**
     * @Gedmo\TreeParent
     * @MongoDB\ReferenceOne(targetDocument="Tag", inversedBy="children")
     */
    private $parent;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Tag", mappedBy="parent", sort={"cod": 1})
     */
    private $children = array();

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

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $properties = array();

    public function __construct()
    {
        $this->children = array();
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
        if ($locale == null) {
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
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->title[$locale])) {
            return '';
        }

        return $this->title[$locale];
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
        if ($locale == null) {
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
        if ($locale == null) {
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
     * @param \Date $created
     *
     * @return Tag
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return Date
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param \Date $updated
     *
     * @return Tag
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return Date
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set translatable locale.
     *
     * @param locale $locale
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
        return $this->children[] = $tag;
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
        if (strpos($this->getPath(), sprintf('%s|', $tagCod)) === 0) {
            return true;
        }

        return strpos($this->getPath(), sprintf('|%s|', $tagCod)) === false ? false : true;
    }

    /**
     * Get properties, null if none.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set properties.
     *
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Get property, null if none.
     *
     * @param string $key
     *
     * @return string
     */
    public function getProperty($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }

        return null;
    }

    /**
     * Set property.
     *
     * @param string $key
     * @param string $value
     */
    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * Returns true if the tag is a PUB tag (that appears in the Pub tab in the back-office).
     */
    public function isPubTag()
    {
        return $this->isDescendantOfByCod('PUBCHANNELS') || $this->isDescendantOfByCod('PUBDECISIONS');
    }
}
