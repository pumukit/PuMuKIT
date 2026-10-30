<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;

trait Keywords
{
    #[MongoDB\Field(type: 'raw')]
    private $keywords = ['en' => []];

    public function setI18nKeyword(array $keyword): void
    {
        $keywords = [];
        foreach ($keyword as $lang => $value) {
            if (null !== $value) {
                $keywords[$lang] = self::normalizeKeywords($value);
            }
        }

        $this->keywords = $keywords;
    }

    public function getI18nKeyword(): array
    {
        $keywords = [];
        foreach ($this->keywords as $lang => $value) {
            $keywords[$lang] = implode(',', self::normalizeKeywords($value));
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

        $this->keywords[$locale] = self::normalizeKeywords($this->keywords[$locale] ?? []);
        $this->keywords[$locale][] = $keyword;

        return true;
    }

    public function removeKeyword($keyword, $locale = null): bool
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        $keywords = self::normalizeKeywords($this->keywords[$locale] ?? []);
        $key = array_search($keyword, $keywords, true);

        if (false !== $key) {
            unset($keywords[$key]);
            $this->keywords[$locale] = $keywords;

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

        return self::normalizeKeywords($this->keywords[$locale] ?? []);
    }

    public function getKeywordsAsString($locale = null): string
    {
        return implode(',', $this->getKeywords($locale));
    }

    public function setI18nKeywords(array $keywords): void
    {
        $this->keywords = array_map(self::normalizeKeywords(...), $keywords);
    }

    public function getI18nKeywords(): array
    {
        return array_map(self::normalizeKeywords(...), $this->keywords);
    }

    private static function normalizeKeywords(mixed $keywords): array
    {
        if (is_string($keywords)) {
            return array_values(array_filter(array_map('trim', explode(',', $keywords))));
        }

        return is_array($keywords) ? $keywords : [];
    }
}
