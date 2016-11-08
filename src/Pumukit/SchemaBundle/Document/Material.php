<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Material.
 *
 * @MongoDB\EmbeddedDocument
 */
class Material extends Element
{
    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $name = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $language;

    /**
     * Set name.
     *
     * @param string      $name
     * @param string|null $locale
     */
    public function setName($name, $locale = null)
    {
        if ($locale == null) {
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
        if ($locale == null) {
            $locale = $this->getLocale();
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
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

    /**
     * Set language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
