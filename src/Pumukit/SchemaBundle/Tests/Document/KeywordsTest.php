<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @internal
 */
#[CoversNothing]
class KeywordsTest extends TestCase
{
    #[DataProvider('documents')]
    public function testEmptyStringStoredAsKeywords(string $class): void
    {
        $document = $this->documentWithStoredKeywords($class, ['en' => '']);

        static::assertSame([], $document->getKeywords('en'));
        static::assertSame('', $document->getKeywordsAsString('en'));
        static::assertSame(['en' => ''], $document->getI18nKeyword());
        static::assertSame(['en' => []], $document->getI18nKeywords());
        static::assertFalse($document->containsKeyword('', 'en'));
        static::assertFalse($document->removeKeyword('dog', 'en'));

        $document->addKeyword('dog', 'en');

        static::assertSame(['dog'], $document->getKeywords('en'));
    }

    #[DataProvider('documents')]
    public function testCommaSeparatedStringStoredAsKeywords(string $class): void
    {
        $document = $this->documentWithStoredKeywords($class, ['en' => 'dog, cat,,bird ', 'es' => ['perro']]);

        static::assertSame(['dog', 'cat', 'bird'], $document->getKeywords('en'));
        static::assertSame(['perro'], $document->getKeywords('es'));
        static::assertSame(['en' => 'dog,cat,bird', 'es' => 'perro'], $document->getI18nKeyword());
        static::assertTrue($document->containsKeyword('cat', 'en'));
        static::assertTrue($document->removeKeyword('cat', 'en'));
        static::assertSame(['dog', 'bird'], array_values($document->getKeywords('en')));
    }

    #[DataProvider('documents')]
    public function testRemoveKeywordFromMissingLocale(string $class): void
    {
        $document = $this->documentWithStoredKeywords($class, ['en' => ['dog']]);

        static::assertFalse($document->removeKeyword('dog', 'es'));
        static::assertSame(['en' => ['dog']], $document->getI18nKeywords());
    }

    #[DataProvider('documents')]
    public function testSetI18nKeywordsNormalizesStrings(string $class): void
    {
        $document = new $class();
        $document->setI18nKeywords(['en' => '', 'es' => 'perro, gato', 'gl' => ['can']]);

        static::assertSame(['en' => [], 'es' => ['perro', 'gato'], 'gl' => ['can']], $document->getI18nKeywords());
        static::assertSame(['en' => [], 'es' => ['perro', 'gato'], 'gl' => ['can']], (new \ReflectionProperty($class, 'keywords'))->getValue($document));
    }

    public static function documents(): iterable
    {
        yield 'series' => [Series::class];

        yield 'multimedia object' => [MultimediaObject::class];
    }

    private function documentWithStoredKeywords(string $class, array $keywords): MultimediaObject|Series
    {
        $document = new $class();
        (new \ReflectionProperty($class, 'keywords'))->setValue($document, $keywords);

        return $document;
    }
}
