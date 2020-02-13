<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\SeriesType;

/**
 * @internal
 * @coversNothing
 */
class SeriesTypeTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $name = "Jules' sermon";
        $description = 'Ezekiel 25:17. The path of the righteous man is beset on all sides by the iniquities of the selfish and the tyranny of evil men.';
        $cod = 'cod';
        $locale = 'en';

        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);
        $series_type->setCod($cod);
        $series_type->setLocale($locale);

        static::assertEquals($name, $series_type->getName());
        static::assertEquals($description, $series_type->getDescription());
        static::assertEquals($cod, $series_type->getCod());
        static::assertEquals($locale, $series_type->getLocale());

        $nameEs = 'Julio Sermon';
        $descriptionEs = 'Ezequiel 25:17. El camino recto del hombre está por todos lados por las iniquidades de los egoístas y la tiranía de los malos hombres.';
        $localeEs = 'es';

        $nameI18n = [$locale => $name, $localeEs => $nameEs];
        $descriptionI18n = [$locale => $description, $localeEs => $descriptionEs];

        $series_type->setI18nName($nameI18n);
        $series_type->setI18nDescription($descriptionI18n);

        static::assertEquals($nameI18n, $series_type->getI18nName());
        static::assertEquals($descriptionI18n, $series_type->getI18nDescription());

        $name = null;
        $description = null;

        $series_type->setName($name);
        $series_type->setDescription($description);

        static::assertEquals(null, $series_type->getName());
        static::assertEquals(null, $series_type->getDescription());
    }

    public function testToString()
    {
        $series_type = new SeriesType();
        static::assertEquals($series_type->getName(), $series_type->__toString());
    }
}
