<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Link.
 *
 * @MongoDB\EmbeddedDocument
 */
class Link extends Element
{
    /**
     * @var array
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
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
            $locale = $this->getLocale();
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
            $locale = $this->getLocale();
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
     * Get I18n name.
     *
     * @return array
     */
    public function getI18nName()
    {
        return $this->name;
    }
}
