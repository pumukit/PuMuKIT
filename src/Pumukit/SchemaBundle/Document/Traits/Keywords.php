<?php

namespace Pumukit\SchemaBundle\Document\Traits;

trait Keywords
{
    /**
     * @var string $keyword
     *
     * @deprecated in version 2.3
     * use keywords instead
     *
     * @MongoDB\Raw
     */
    private $keyword = array('en' => '');

    /**
     * @var array $keywords
     *
     * @MongoDB\Raw
     */
    private $keywords = array('en' => array());

    /**
     * Set keyword
     *
     * @deprecated in version 2.3
     * use setKeywords instead
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
        $this->keywords[$locale] = array_filter(array_map('trim', explode(',', $keyword)));
    }

    /**
     * Get keyword
     *
     * @deprecated in version 2.3
     * use getKeywords instead
     *
     * @param string|null $locale
     * @return string
     */
    public function getKeyword($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->keywords[$locale])) {
            return '';
        }

        return implode(',', $this->keywords[$locale]);
    }

    /**
     * Set I18n keyword
     *
     * @param array $keyword
     */
    public function setI18nKeyword(array $keyword)
    {
        $keywords = array();
        foreach($keyword as $lang => $value) {
            $keywords[$lang] = array_filter(array_map('trim', explode(',', $value)));
        }

        $this->keywords = $keywords;
        $this->keyword = $keyword;
    }

    /**
     * Get i18n keyword
     *
     * @return array
     */
    public function getI18nKeyword()
    {
        $keywords = array();
        foreach($this->keywords as $lang => $value) {
            $keywords[$lang] = implode(',', $value);
        }

        return $keywords;
    }

    /**
     * Contains keyword
     *
     *
     * @param string|null $locale
     * @return boolean    TRUE if this multimedia_object contained the keyword, FALSE otherwise.
     */
    public function containsKeyword($keyword, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }

        return in_array($keyword, $this->getKeywords($locale), true);
    }

    /**
     * Add keyword
     *
     * @param string      $keyword
     * @param string|null $locale
     * @return boolean Always TRUE.
     */
    public function addKeyword($keyword, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }

        if (!isset($this->keyword[$locale])) {
            return array($locale => $keyword);
        }

        $this->keyword[$locale] = $keyword;
        return true;
    }

    /**
     * Remove keyword
     *
     * @param string      $keyword
     * @param string|null $locale
     * @return boolean TRUE if object contains the keyword
     */
    public function removeKeyword($keyword, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }

        if (!isset($this->keyword[$locale])) {
            return false;
        }

        $key = array_search($$keyword, $this->keywords[$lang], true);

        if ($key !== false) {
            unset($this->keywords[$lang][$key]);

            return true;
        }
        return false;
    }


    /**
     * Set keywords
     *
     * @param array $keyword
     * @param string|null $locale
     */
    public function setKeywords(array $keywords, $locale = null)
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
     * @return array
     */
    public function getKeywords($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->keyword[$locale])) {
            return array();
        }

        return $this->keyword[$locale];
    }

    /**
     * Set I18n keywords
     *
     * @param array $keywords
     */
    public function setI18nKeywords(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Get i18n keywords
     *
     * @return array
     */
    public function getI18nKeywords()
    {
        return $this->keywords;
    }

}