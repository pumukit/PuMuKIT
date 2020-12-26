<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Keywords
{
    /**
     * @MongoDB\Field(type="raw")
     */
    private $keywords = ['en' => []];

    public function setI18nKeyword(array $keyword): void
    {
        $keywords = [];
        foreach ($keyword as $lang => $value) {
            $keywords[$lang] = array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        $this->keywords = $keywords;
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

    public function addKeyword(?string $keyword, $locale = null): bool
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        if (!isset($this->keywords[$locale])) {
            $this->keywords[$locale] = [];
        }

        $this->keywords[$locale][] = $keyword;

        return true;
    }

    public function removeKeyword($keyword, $locale = null): bool
    {
        if (null === $locale) {
            $locale = $this->locale;
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

        return $this->keywords[$locale] ?? [];
    }

    public function getKeywordsAsString($locale = null): string
    {
        return implode(',', $this->getKeywords($locale));
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
