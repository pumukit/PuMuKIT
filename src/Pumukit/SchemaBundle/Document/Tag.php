<?php

namespace Pumukit\SchemaBundle\Document;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Pumukit\SchemaBundle\Document\Tag
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\TagRepository")
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
class Tag
{
  /**
   * @var integer $id
   *
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var int $number_multimedia_objects
   *
   * @MongoDB\Int
   */
  private $number_multimedia_objects = 0;

  /**
   * @var string $title
   * //Translatable
   *
   * @MongoDB\Raw
   */
  private $title = array('en' => '');

  /**
   * @var string $description
   * //Translatable
   *
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

  /**
   * @var string $slug
   *
   * @MongoDB\String
   */
  private $slug;

  /**
   * @var string $cod
   *
   * @MongoDB\String
   * @MongoDB\UniqueIndex(order="asc")
   * @Assert\Regex("/^\w*$/")
   * @Gedmo\TreePathSource
   *
   * TODO Unique Index #6098
   */
  private $cod = "";

  /**
   * @var boolean $metatag
   *
   * @MongoDB\Boolean
   */
  private $metatag = false;

  /**
   * @var boolean $display
   *
   * @MongoDB\Boolean
   */
  private $display = false;

  /**
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   * @var locale $locale
   */
  private $locale = 'en';

  /**
   * @var date $created
   *
   * @MongoDB\Date
   */
  private $created;

  /**
   * @var date $updated
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
   * @MongoDB\ReferenceMany(targetDocument="Tag", mappedBy="parent")
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

    public function __construct()
    {
        $this->children = array();
    }

  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
      return $this->id;
  }

  /**
   * Set title
   *
   * @param string $title
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
   * Get title
   *
   * @param string|null $locale
   * @return string
   */
  public function getTitle($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->title[$locale])) {
          return;
      }

      return $this->title[$locale];
  }

  /**
   * Get i18n title
   *
   * @return array
   */
  public function getI18nTitle()
  {
      return $this->title;
  }

  /**
   * Set i18n title
   *
   * @param array $title
   */
  public function setI18nTitle(array $title)
  {
      $this->title = $title;
  }

  /**
   * Set description
   *
   * @param string $description
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
   * Get description
   *
   * @param string|null $locale
   * @return string
   */
  public function getDescription($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->description[$locale])) {
          return;
      }

      return $this->description[$locale];
  }

  /**
   * Set i18n description
   *
   * @param array $description
   */
  public function setI18nDescription(array $description)
  {
      $this->description = $description;
  }

  /**
   * Get i18n description
   *
   * @return array
   */
  public function getI18nDescription()
  {
      return $this->description;
  }

  /**
   * Set slug
   *
   * @param string $slug
   * @return Tag
   */
  public function setSlug($slug)
  {
      $this->slug = $slug;

      return $this;
  }

  /**
   * Get slug
   *
   * @return string
   */
  public function getSlug()
  {
      return $this->slug;
  }

  /**
   * Set cod
   *
   * @param string $cod
   */
  public function setCod($cod)
  {
      $this->cod = $cod;
  }

  /**
   * Get cod
   *
   * @return string
   */
  public function getCod()
  {
      return $this->cod;
  }

  /**
   * Set metatag
   *
   * @param boolean $metatag
   */
  public function setMetatag($metatag)
  {
      $this->metatag = $metatag;
  }

  /**
   * Get metatag
   *
   * @return boolean
   */
  public function getMetatag()
  {
      return $this->metatag;
  }

  /**
   * Set display
   *
   * @param boolean $display
   */
  public function setDisplay($display)
  {
      $this->display = $display;
  }

  /**
   * Get display
   *
   * @return boolean
   */
  public function getDisplay()
  {
      return $this->display;
  }

  /**
   * Set created
   *
   * @param \Date $created
   * @return Tag
   */
  public function setCreated($created)
  {
      $this->created = $created;

      return $this;
  }

  /**
   * Get created
   *
   * @return Date
   *
   */
  public function getCreated()
  {
      return $this->created;
  }

  /**
   * Set updated
   *
   * @param \Date $updated
   * @return Tag
   */
  public function setUpdated($updated)
  {
      $this->updated = $updated;

      return $this;
  }

  /**
   * Get updated
   *
   * @return Date
   */
  public function getUpdated()
  {
      return $this->updated;
  }

  /**
   * Set translatable locale
   *
   * @param locale $locale
   */
  public function setLocale($locale)
  {
      $this->locale = $locale;
  }

  /**
   * Get locale
   *
   * @return string
   */
  public function getLocale()
  {
      return $this->locale;
  }

  /**
   * to string
   *
   * @return string
   */
  public function __toString()
  {
      return $this->getTitle();
  }

  /**
   * Increase number_multimedia_objects
   */
  public function increaseNumberMultimediaObjects()
  {
      ++$this->number_multimedia_objects;
  }

  /**
   * Decrease number_multimedia_objects
   */
  public function decreaseNumberMultimediaObjects()
  {
      --$this->number_multimedia_objects;
  }

  /**
   * Get number_multimedia_objects
   */
  public function getNumberMultimediaObjects()
  {
      return $this->number_multimedia_objects;
  }

  /**
   * Set number_multimedia_objects
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

    public function addChild(Tag $tag)
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
   * Returns true if given node is children of tag
   *
   * @param Tag $tag
   *
   * @return bool
   */
  public function isChildrenOf(Tag $tag)
  {
      return $tag == $this->getParent();
  }

  /**
   * Returns true if given node is descendant of tag
   *
   * @param Tag $tag
   *
   * @return bool
   */
  public function isDescendantOf(Tag $tag)
  {
      if ($tag == $this) {
          return false;
      }

      return substr($this->getPath(), 0, strlen($tag->getPath())) === $tag->getPath();
  }
}
