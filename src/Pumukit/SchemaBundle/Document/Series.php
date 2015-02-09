<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesRepository")
 */
class Series
{
  /**
   * @MongoDB\Id
   */
  protected $id;

  /**
   * @MongoDB\ReferenceOne(targetDocument="SeriesType", inversedBy="series", simple=true)
   */
  private $series_type;

  /**
   * @var ArrayCollection $multimedia_objects
   *
   * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", mappedBy="series", repositoryMethod="findWithoutPrototype", sort={"rank"=1}, simple=true, orphanRemoval=true, cascade="ALL")
   */
  private $multimedia_objects;

  /**
   * @var ArrayCollection $pics
   *
   * @MongoDB\EmbedMany(targetDocument="Pic")
   */
  private $pics;

  /**
   * @var boolean $announce
   *
   * @MongoDB\Boolean
   */
  private $announce = false;

  /**
   * @var datetime $public_date
   *
   * @MongoDB\Date
   */
  private $public_date;

  /**
   * @var string $title
   *
   * @MongoDB\Raw
   */
  private $title = array('en' => '');

  /**
   * @var string $subtitle
   *
   * @MongoDB\Raw
   */
  private $subtitle = array('en' => '');

  /**
   * @var text $description
   *
   * @MongoDB\Raw
   */
  private $description = array('en' => '');

  /**
   * @var text $header
   *
   * @MongoDB\Raw
   */
  private $header = array('en' => '');

  /**
   * @var text $footer
   *
   * @MongoDB\Raw
   */
  private $footer = array('en' => '');

  /**
   * @var string $copyright
   *
   * @MongoDB\Raw
   */
  private $copyright = array('en' => '');

  /**
   * @var string $keyword
   *
   * @MongoDB\Raw
   */
  private $keyword = array('en' => '');

  /**
   * @var string $line2
   *
   * @MongoDB\Raw
   */
  private $line2 = array('en' => '');

  /**
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   * @var locale $locale
   */
  private $locale = 'en';

    public function __construct()
    {
        $this->multimedia_objects = new ArrayCollection();
        $this->pics = new ArrayCollection();
    }

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
   * Set series_type
   *
   * @param SeriesType $series_type
   */
  public function setSeriesType(SeriesType $series_type)
  {
      $this->series_type = $series_type;
  }

  /**
   * Get series_type
   *
   * @return SeriesType
   */
  public function getSeriesType()
  {
      return $this->series_type;
  }


  /**
   * Contains multimedia_object
   *
   * @param MultimediaObject $multimedia_object
   *
   * @return boolean
   */
  public function containsMultimediaObject(MultimediaObject $multimedia_object)
  {
      return $this->multimedia_objects->contains($multimedia_object);
  }

  /**
   * Get multimedia_objects
   *
   * @return ArrayCollection
   */
  public function getMultimediaObjects()
  {
      return $this->multimedia_objects;
  }

  /**
   * Set announce
   *
   * @param boolean $announce
   */
  public function setAnnounce($announce)
  {
      $this->announce = $announce;
  }

  /**
   * Get announce
   *
   * @return boolean
   */
  public function getAnnounce()
  {
      return $this->announce;
  }

  /**
   * Set public_date
   *
   * @param datetime $public_date
   */
  public function setPublicDate($public_date)
  {
      $this->public_date = $public_date;
  }

  /**
   * Get public_date
   *
   * @return datetime
   */
  public function getPublicDate()
  {
      return $this->public_date;
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
   * Set I18n title
   *
   * @param array $title
   */
  public function setI18nTitle(array $title)
  {
      $this->title = $title;
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
   * Set subtitle
   *
   * @param string $subtitle
   * @param string|null $locale 
   */
  public function setSubtitle($subtitle, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->subtitle[$locale] = $subtitle;
  }

  /**
   * Get subtitle
   *
   * @param string|null $locale 
   * @return string
   */
  public function getSubtitle($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->subtitle[$locale])) {
          return;
      }

      return $this->subtitle[$locale];
  }

  /**
   * Set I18n subtitle
   *
   * @param array $subtitle
   */
  public function setI18nSubtitle(array $subtitle)
  {
      $this->subtitle = $subtitle;
  }

  /**
   * Get i18n subtitle
   *
   * @return array
   */
  public function getI18nSubtitle()
  {
      return $this->subtitle;
  }

  /**
   * Set description
   *
   * @param text $description
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
   * @return text
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
   * Set I18n description
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
   * Set header
   *
   * @param text $header
   * @param string|null $locale 
   */
  public function setHeader($header, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->header[$locale] = $header;
  }

  /**
   * Get header
   *
   * @param string|null $locale 
   * @return text
   */
  public function getHeader($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->header[$locale])) {
          return;
      }

      return $this->header[$locale];
  }

  /**
   * Set I18n header
   *
   * @param array $header
   */
  public function setI18nHeader(array $header)
  {
      $this->header = $header;
  }

  /**
   * Get i18n header
   *
   * @return array
   */
  public function getI18nHeader()
  {
      return $this->header;
  }

  /**
   * Set footer
   *
   * @param text $footer
   * @param string|null $locale 
   */
  public function setFooter($footer, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->footer[$locale] = $footer;
  }

  /**
   * Get footer
   *
   * @param string|null $locale 
   * @return text
   */
  public function getFooter($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->footer[$locale])) {
          return;
      }

      return $this->footer[$locale];
  }

  /**
   * Set I18n footer
   *
   * @param array $footer
   */
  public function setI18nFooter(array $footer)
  {
      $this->footer = $footer;
  }

  /**
   * Get i18n footer
   *
   * @return array
   */
  public function getI18nFooter()
  {
      return $this->footer;
  }

  /**
   * Set copyright
   *
   * @param string $copyright
   * @param string|null $locale 
   */
  public function setCopyright($copyright, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->copyright[$locale] = $copyright;
  }

  /**
   * Get copyright
   *
   * @param string|null $locale 
   * @return string
   */
  public function getCopyright($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->copyright[$locale])) {
          return;
      }

      return $this->copyright[$locale];
  }

  /**
   * Set I18n copyright
   *
   * @param array $copyright
   */
  public function setI18nCopyright(array $copyright)
  {
      $this->copyright = $copyright;
  }

  /**
   * Get i18n copyright
   *
   * @return array
   */
  public function getI18nCopyright()
  {
      return $this->copyright;
  }

  /**
   * Set keyword
   *
   * @param string $keyword
   * @param string|null $locale 
   */
  public function setKeyword($keyword, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->keyword[$locale] = $keyword;
  }

  /**
   * Get keyword
   *
   * @param string|null $locale 
   * @return string
   */
  public function getKeyword($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->keyword[$locale])) {
          return;
      }

      return $this->keyword[$locale];
  }

  /**
   * Set I18n keyword
   *
   * @param array $keyword
   */
  public function setI18nKeyword(array $keyword)
  {
      $this->keyword = $keyword;
  }

  /**
   * Get i18n keyword
   *
   * @return array
   */
  public function getI18nKeyword()
  {
      return $this->keyword;
  }

  /**
   * Set line2
   *
   * @param string $line2
   * @param string|null $locale 
   */
  public function setLine2($line2, $locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      $this->line2[$locale] = $line2;
  }

  /**
   * Get line2
   *
   * @param string|null $locale 
   * @return string
   */
  public function getLine2($locale = null)
  {
      if ($locale == null) {
          $locale = $this->locale;
      }
      if (!isset($this->line2[$locale])) {
          return;
      }

      return $this->line2[$locale];
  }

  /**
   * Set I18n line2
   *
   * @param array $line2
   */
  public function setI18nLine2(array $line2)
  {
      $this->line2 = $line2;
  }

  /**
   * Get i18n line2
   *
   * @return array
   */
  public function getI18nLine2()
  {
      return $this->line2;
  }

    public function __toString()
    {
        return $this->getTitle();
    }

  /**
   * Set locale
   *
   * @param string $locale
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
   * Contains multimediaobject with tags
   *
   * @param Tag $tag
   * @return boolean
   */
  public function containsMultimediaObjectWithTag(Tag $tag)
  {
      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsTag($tag)) {
              return true;
          }
      }

      return false;
  }

  /**
   * Get multimediaobjects with a tag
   *
   * @param Tag $tag
   * @return ArrayCollection
   */
  public function getMultimediaObjectsWithTag(Tag $tag)
  {
      $r = array();

      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsTag($tag)) {
              $r[] = $mmo;
          }
      }

      return $r;
  }

  /**
   * Get one multimedia object with tag
   *
   * @param Tag $tag
   * @return MultimediaObject
   */
  public function getMultimediaObjectWithTag(Tag $tag)
  {
      foreach ($this->multimedia_objects as $mmo) {
      //if ($mmo->tags->contains($tag)) {
      //FIXME no pasa el test phpunit cuando se llama desde seriestest
      if ($mmo->containsTag($tag)) {
          return $mmo;
      }
      }

      return;
  }

  /**
   * Get multimediaobjects with all tags
   *
   * @param array $tags
   * @return ArrayCollection
   */
  public function getMultimediaObjectsWithAllTags(array $tags)
  {
      $r = array();
      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsAllTags($tags)) {
              $r[] = $mmo;
          }
      }

      return $r;
  }

  /**
   * Get multimediaobject with all tags
   *
   * @param array $tags
   * @return multimedia_object
   */
  public function getMultimediaObjectWithAllTags(array $tags)
  {
      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsAllTags($tags)) {
              return $mmo;
          }
      }

      return;
  }

  /**
   * Get multimediaobjects with any tag
   *
   * @param array $tags
   * @return ArrayCollection
   */
  public function getMultimediaObjectsWithAnyTag(array $tags)
  {
      $r = array();

      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsAnyTag($tags)) {
              $r[] = $mmo;
          }
      }

      return $r;
  }

  /**
   * Get multimediaobject with any tag
   *
   * @param array $tags
   * @return MultimediaObject
   */
  public function getMultimediaObjectWithAnyTag(array $tags)
  {
      foreach ($this->multimedia_objects as $mmo) {
          if ($mmo->containsAnyTag($tags)) {
              return $mmo;
          }
      }

      return;
  }

  /**
   * Get filtered multimedia objects with tags
   *
   * @param array $any_tags
   * @param array $all_tags
   * @param array $not_any_tags
   * @param array $not_all_tags
   * @return ArrayCollection
   */
  public function getFilteredMultimediaObjectsWithTags(
                             array $any_tags = array(),
                             array $all_tags = array(),
                             array $not_any_tags = array(),
                             array $not_all_tags = array())
  {
      $r = array();

      foreach ($this->multimedia_objects as $mmo) {
          if ($any_tags && !$mmo->containsAnyTag($any_tags)) {
              continue;
          }
          if ($all_tags && !$mmo->containsAllTags($all_tags)) {
              continue;
          }
          if ($not_any_tags && $mmo->containsAnyTag($not_any_tags)) {
              continue;
          }
          if ($not_all_tags && $mmo->containsAllTags($not_all_tags)) {
              continue;
          }

          $r[] = $mmo;
      }

      return $r;
  }

  /**
   * Add pic
   *
   * @param Pic $pic
   */
  public function addPic(Pic $pic)
  {
      $this->pics->add($pic);
  }

  /**
   * Remove pic
   *
   * @param Pic $pic
   */
  public function removePic(Pic $pic)
  {
      $this->pics->removeElement($pic);
  }

  /**
   * Remove pic by id
   *
   * @param string $picId
   */
  public function removePicById($picId)
  {
      $this->pics = $this->pics->filter(function ($pic) use ($picId) {
      return $pic->getId() !== $picId;
      });
  }

  /**
   * Up pic by id
   *
   * @param string $picId
   */
  public function upPicById($picId)
  {
      $this->reorderPicById($picId, true);
  }

  /**
   * Down pic by id
   *
   * @param string $picId
   */
  public function downPicById($picId)
  {
      $this->reorderPicById($picId, false);
  }

  /**
   * Reorder pic by id
   *
   * @param string $picId
   * @param boolean $up
   */
  private function reorderPicById($picId, $up = true)
  {
      $snapshot = array_values($this->pics->toArray());
      $this->pics->clear();

      $out = array();
      foreach ($snapshot as $key => $pic) {
          if ($pic->getId() === $picId) {
              $out[($key * 10) + ($up ? -11 : 11) ] = $pic;
          } else {
              $out[$key * 10] = $pic;
          }
      }

      ksort($out);
      foreach ($out as $pic) {
          $this->pics->add($pic);
      }
  }

  /**
   * Contains pic
   *
   * @param Pic $pic
   *
   * @return boolean
   */
  public function containsPic(Pic $pic)
  {
      return $this->pics->contains($pic);
  }

  /**
   * Get pics
   *
   * @return ArrayCollection
   */
  public function getPics()
  {
      return $this->pics;
  }

  /**
   * Get pic by id
   *
   * @param $picId
   *
   * @return Pic|null
   */
  public function getPicById($picId)
  {
      foreach ($this->pics as $pic) {
          if ($pic->getId() == $picId) {
              return $pic;
          }
      }

      return;
  }

  /**
   * Get first pic url
   *
   * @return string
   */
  public function getFirstUrlPic()
  {
      $url = '';
      foreach ($this->pics as $pic) {
          if (null !== $pic->getUrl()) {
              $url = $pic->getUrl();
              break;
          }
      }

      return $url;
  }
}
