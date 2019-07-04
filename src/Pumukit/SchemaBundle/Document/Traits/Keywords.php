<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Keywords
{
    /**
     * @var string
     *
     * @deprecated in version 2.3
     * use keywords instead
     *
     * @MongoDB\Field(type="raw")
     */
    private $keyword = ['en' => ''];

    /**
     * @var array
     *
     * @MongoDB\Field(type="raw")
     */
    private $keywords = ['en' => []];

    /**
     * Set keyword.
     *
     * @deprecated in version 2.3
     * use setKeywords instead
     *
     * @param string      $keyword
     * @param null|string $locale
     */
    public function setKeyword($keyword, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->keyword[$locale] = $keyword;
        $this->keywords[$locale] = array_values(array_filter(array_map('trim', explode(',', $keyword))));
    }

    /**
     * Get keyword.
     *
     * @deprecated in version 2.3
     * use getKeywords instead
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getKeyword($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->keywords[$locale])) {
            return '';
        }

        return implode(',', $this->keywords[$locale]);
    }

    /**
     * Set I18n keyword.
     *
     * @param array $keyword
     */
    public function setI18nKeyword(array $keyword)
    {
        $keywords = [];
        foreach ($keyword as $lang => $value) {
            $keywords[$lang] = array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        $this->keywords = $keywords;
        $this->keyword = $keyword;
    }

    /**
     * Get i18n keyword.
     *
     * @return array
     */
    public function getI18nKeyword()
    {
        $keywords = [];
        foreach ($this->keywords as $lang => $value) {
            $keywords[$lang] = implode(',', $value);
        }

        return $keywords;
    }

    /**
     * Contains keyword.
     *
     * @param null|string $locale
     * @param mixed       $keyword
     *
     * @return bool TRUE if this multimedia_object contained the keyword, FALSE otherwise
     */
    public function containsKeyword($keyword, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return in_array($keyword, $this->getKeywords($locale), true);
    }

    /**
     * Add keyword.
     *
     * @param string      $keyword
     * @param null|string $locale
     *
     * @return bool Always TRUE
     */
    public function addKeyword($keyword, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        if (!isset($this->keyword[$locale])) {
            return [$locale => $keyword];
        }

        $this->keyword[$locale] = $keyword;

        return true;
    }

    /**
     * Remove keyword.
     *
     * @param string      $keyword
     * @param null|string $locale
     *
     * @return bool TRUE if object contains the keyword
     */
    public function removeKeyword($keyword, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        if (!isset($this->keyword[$locale])) {
            return false;
        }

        $key = array_search($keyword, $this->keywords[$locale], true);

        if (false !== $key) {
            unset($this->keywords[$locale][$key]);

            return true;
        }

        return false;
    }

    /**
     * Set keywords.
     *
     * @param array       $keyword
     * @param null|string $locale
     */
    public function setKeywords(array $keywords, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->keywords[$locale] = $keywords;
    }

    /**
     * Get keyword.
     *
     * @param null|string $locale
     *
     * @return array
     */
    public function getKeywords($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->keyword[$locale])) {
            return [];
        }

        return $this->keyword[$locale];
    }

    /**
     * Set I18n keywords.
     *
     * @param array $keywords
     */
    public function setI18nKeywords(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Get i18n keywords.
     *
     * @return array
     */
    public function getI18nKeywords()
    {
        return $this->keywords;
    }
}
