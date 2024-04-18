<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests;

use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Services\i18nService;

final class i18nServiceTest extends TestCase
{
    private array $locales;
    private string $defaultLocale;
    private i18nService $i18nService;

    protected function setUp(): void
    {
        $this->locales = ['en', 'es'];
        $this->defaultLocale = 'en';
        $this->i18nService = new i18nService($this->locales, $this->defaultLocale);
    }

    public function testGetLocales(): void
    {
        $locales = $this->i18nService->getLocales();

        $this->assertSame($this->locales, $locales);
    }

    public function testGetDefaultLocale(): void
    {
        $defaultLocale = $this->i18nService->getDefaultLocale();

        $this->assertSame($this->defaultLocale, $defaultLocale);
    }

    public function testGenerateI18nText(): void
    {
        $text = 'Example text';
        $i18nText = $this->i18nService->generateI18nText($text);

        foreach ($this->locales as $locale) {
            $this->assertSame($text, $i18nText[$locale]);
        }
    }
}
