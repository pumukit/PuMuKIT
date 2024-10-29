<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document\ValueObject;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;

/**
 * @internal
 *
 * @coversNothing
 */
final class i18nTextTest extends TestCase
{
    public function testCreate(): void
    {
        $sampleText = ['en' => 'Hello', 'es' => 'Hola'];
        $i18nText = i18nText::create($sampleText);
        $this->assertInstanceOf(i18nText::class, $i18nText);
    }

    public function testTextFromLocale(): void
    {
        $sampleText = ['en' => 'Hello', 'es' => 'Hola'];
        $i18nText = i18nText::create($sampleText);
        $this->assertSame('Hello', $i18nText->textFromLocale('en'));
        $this->assertSame('Hola', $i18nText->textFromLocale('es'));
        $this->assertSame('', $i18nText->textFromLocale('fr'));
    }

    public function testAll(): void
    {
        $sampleText = ['en' => 'Hello', 'es' => 'Hola'];
        $i18nText = i18nText::create($sampleText);
        $this->assertSame($sampleText, $i18nText->all());
    }
}
