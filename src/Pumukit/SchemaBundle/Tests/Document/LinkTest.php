<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Link;

/**
 * @internal
 *
 * @coversNothing
 */
class LinkTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $name = 'name';
        $locale = 'en';

        $link = new Link();
        $link->setName($name);

        static::assertEquals($name, $link->getName());

        $nameEs = 'nombre';
        $localeEs = 'es';

        $nameI18n = [$locale => $name, $localeEs => $nameEs];

        $link->setI18nName($nameI18n);

        static::assertEquals($nameI18n, $link->getI18nName());

        $name = null;

        $link->setName($name);
        static::assertEquals($name, $link->getName());
    }
}
