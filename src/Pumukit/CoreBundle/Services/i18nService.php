<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

final class i18nService
{
    private array $locales;
    private string $defaultLocale;

    public function __construct(array $locales, string $defaultLocale)
    {
        $this->locales = $locales;
        $this->defaultLocale = $defaultLocale;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function generateI18nText(string $text): array
    {
        $i18nText = [];
        foreach ($this->locales as $locale) {
            $i18nText[$locale] = $text;
        }

        return $i18nText;
    }
}
