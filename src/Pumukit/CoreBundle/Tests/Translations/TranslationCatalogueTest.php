<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Translations;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
class TranslationCatalogueTest extends TestCase
{
    private const array LOCALES = ['en', 'es', 'ca', 'gl', 'eu', 'val'];

    public function testEveryEnglishKeyIsTranslatedInEveryLocale(): void
    {
        $catalogues = $this->catalogues();
        $missing = [];

        foreach (array_diff(self::LOCALES, ['en']) as $locale) {
            foreach ($catalogues['en'] as $domain => $keys) {
                foreach (array_keys($keys) as $key) {
                    if ('' === ($catalogues[$locale][$domain][$key] ?? '')) {
                        $missing[] = sprintf('%s %s: %s', $locale, $domain, $key);
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Translate these keys in the messages.<locale>.xlf of the bundle that has them in English');
    }

    public function testOnlyTheSupportedLocalesHaveCatalogues(): void
    {
        $this->assertSame([], array_values(array_diff(array_keys($this->catalogues()), self::LOCALES)));
    }

    private function catalogues(): array
    {
        $catalogues = [];
        foreach (glob(dirname(__DIR__, 3).'/*/Resources/translations/*.xlf') as $file) {
            [$domain, $locale] = explode('.', basename($file));
            $xliff = simplexml_load_file($file);
            $xliff->registerXPathNamespace('x', 'urn:oasis:names:tc:xliff:document:1.2');
            foreach ($xliff->xpath('//x:trans-unit') as $unit) {
                $key = '' !== (string) $unit['resname'] ? (string) $unit['resname'] : (string) $unit->source;
                $target = trim((string) $unit->target);
                if ('en' === $locale || '' !== $target) {
                    $catalogues[$locale][$domain][$key] = 'en' === $locale ? (string) $unit->source : $target;
                }
            }
        }

        return $catalogues;
    }
}
