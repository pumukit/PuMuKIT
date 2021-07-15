<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;

/**
 * Pumukit\SchemaBundle\Document\SeriesType.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesTypeRepository")
 */
class SeriesType
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var array<string, string>
     *
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @var array<string, string>
     *
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $cod = '0';

    /**
     * @var ArrayCollection
     *
     * @MongoDB\ReferenceMany(targetDocument="Series", mappedBy="series_type", storeAs="id", orphanRemoval=false)
     * @Serializer\Exclude
     */
    private $series;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var string
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->series = new ArrayCollection();
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
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
     * Set name.
     *
     * @param string      $name
     * @param string|null $locale
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
     * @param string|null $locale
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
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get i18n name.
     *
     * @return array
     */
    public function getI18nName()
    {
        return $this->name;
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
     * Set I18n description.
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

    /**
     * Contains series.
     *
     * @return bool
     */
    public function containsSeries(Series $series)
    {
        return $this->series->contains($series);
    }

    /**
     * Get series.
     *
     * @return ArrayCollection
     */
    public function getSeries()
    {
        return $this->series;
    }
}
