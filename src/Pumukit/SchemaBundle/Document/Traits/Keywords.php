<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Keywords
{
    /**
     * @deprecated in version 2.3 use keywords instead
     *
     * @MongoDB\Field(type="raw")
     */
    private $keyword = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $keywords = ['en' => []];

    /**
     * @deprecated in version 2.3 use setKeywords instead
     *
     * @param mixed      $keyword
     * @param mixed|null $locale
     */
    public function setKeyword($keyword, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->keyword[$locale] = $keyword;
        $this->keywords[$locale] = array_values(array_filter(array_map('trim', explode(',', $keyword))));
    }

    /**
     * @deprecated in version 2.3 use getKeywords instead
     */
    public function getKeyword(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->keywords[$locale])) {
            return '';
        }

        return implode(',', $this->keywords[$locale]);
    }

    public function setI18nKeyword(array $keyword): void
    {
        $keywords = [];
        foreach ($keyword as $lang => $value) {
            $keywords[$lang] = array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        $this->keywords = $keywords;
        $this->keyword = $keyword;
    }

    public function getI18nKeyword(): array
    {
        $keywords = [];
        foreach ($this->keywords as $lang => $value) {
            $keywords[$lang] = implode(',', $value);
        }

        return $keywords;
    }

    public function containsKeyword($keyword, $locale = null): bool
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return in_array($keyword, $this->getKeywords($locale), true);
    }

    public function addKeyword($keyword, $locale = null): bool
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        if (!isset($this->keywords[$locale])) {
            $this->keywords[$locale] = [];
        }

        $this->keywords[$locale][] = $keyword;
        $this->keyword[$locale] = implode(',', $this->keywords[$locale]);

        return true;
    }

    public function removeKeyword($keyword, $locale = null): bool
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

    public function setKeywords(array $keywords, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->keywords[$locale] = $keywords;
    }

    public function getKeywords($locale = null): array
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->keyword[$locale] ?? [];
    }

    public function setI18nKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getI18nKeywords(): array
    {
        return $this->keywords;
    }
}
