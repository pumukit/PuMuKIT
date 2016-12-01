<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Element.
 *
 * @MongoDB\MappedSuperclass
 */
class Element
{

    use Traits\Properties;

    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\OneToOne(targetDocument="Element")
     * @MongoDB\JoinColumn(name="ref_id", referencedColumnName="id")
     **/
    //private $ref = null;

    /**
     * @var array
     *
     * @MongoDB\Collection
     */
    private $tags;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $url;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $path;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $mime_type;

    /**
     * @var int
     *
     * @MongoDB\Int
     */
    private $size;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $hide = false;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $description = array('en' => '');

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->tags = array();
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
     * Set ref.
     *
     * @param Pic $ref
     */
    /*public function setRef(Element $ref)
      {
      $this->ref = $ref;
      }*/

    /**
     * Get ref.
     *
     * @return Pic
     */
    /*public function getRef()
      {
      return $this->ref;
      }*/

    /**
     * Set tags.
     *
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Get tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add tag.
     *
     * @param string $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;

        return $this->tags = array_unique($this->tags);
    }

    /**
     * Remove tag.
     *
     * @param string $tag
     *
     * @return bool TRUE if this pic contained the specified tag, FALSE otherwise
     */
    public function removeTag($tag)
    {
        $tag = array_search($tag, $this->tags, true);

        if ($tag !== false) {
            unset($this->tags[$tag]);

            return true;
        }

        return false;
    }

    /**
     * Contains tag.
     *
     * @param string $tag
     *
     * @return bool TRUE if this pic contained the specified tag, FALSE otherwise
     */
    public function containsTag($tag)
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Contains all tags.
     *
     * @param array $tags
     *
     * @return bool TRUE if this pic contained all tags, FALSE otherwise
     */
    public function containsAllTags(array $tags)
    {
        return count(array_intersect($tags, $this->tags)) === count($tags);
    }

    /**
     * Contains any tags.
     *
     * @param array $tags
     *
     * @return bool TRUE if this pic contained any tag of the list, FALSE otherwise
     */
    public function containsAnyTag(array $tags)
    {
        return count(array_intersect($tags, $this->tags)) != 0;
    }

    /**
     * Set url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set path.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set mime_type.
     *
     * @param string $mime_type
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
    }

    /**
     * Get mime_type.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set size.
     *
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set hide.
     *
     * @param bool $hide
     */
    public function setHide($hide)
    {
        $this->hide = $hide;
    }

    /**
     * Get hide.
     *
     * @return bool
     */
    public function getHide()
    {
        return $this->hide;
    }

    /**
     * Set description.
     *
     * @param text        $description
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
     * @return text
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
     * Set I18n description.
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

    public function __clone()
    {
        $this->id = null;
    }
}
