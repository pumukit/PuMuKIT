<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Role;

/**
 * @internal
 *
 * @coversNothing
 */
class RoleTest extends TestCase
{
    public function testDefaults()
    {
        $role = new Role();

        static::assertEquals('0', $role->getCod());
        static::assertTrue($role->getDisplay());
        static::assertEquals(0, $role->getNumberPeopleInMultimediaObject());
        static::assertEquals($role, $role->cloneResource());
    }

    public function testGetterAndSetter()
    {
        $role = new Role();

        $locale = 'en';
        $cod = 'rol1'; // String - max length = 5
        $xml = 'string <xml>';
        $display = true;
        $name1 = 'Presenter';
        $name2 = '';
        $text1 = 'Presenter Role 1';
        $text2 = '';

        $role->setLocale($locale);
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name1);
        $role->setText($text1);

        static::assertEquals($locale, $role->getLocale());
        static::assertEquals($cod, $role->getCod());
        static::assertEquals($xml, $role->getXml());
        static::assertEquals($display, $role->getDisplay());
        static::assertEquals($name1, $role->getName());
        static::assertEquals($text1, $role->getText());

        $role->setName($name2);
        $role->setText($text2);

        static::assertEquals($name2, $role->getName());
        static::assertEquals($text2, $role->getText());

        $nameEs = 'Presentador';
        $textEs = 'Rol de presentador 1';

        $i18nName = ['en' => $name1, 'es' => $nameEs];
        $i18nText = ['en' => $text1, 'es' => $textEs];

        $role->setI18nName($i18nName);
        $role->setI18nText($i18nText);

        static::assertEquals($i18nName, $role->getI18nName());
        static::assertEquals($i18nText, $role->getI18nText());
    }

    public function testNumberPeopleInMultimediaObject()
    {
        $role = new Role();

        static::assertEquals(0, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        static::assertEquals(1, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        $role->increaseNumberPeopleInMultimediaObject();
        static::assertEquals(3, $role->getNumberPeopleInMultimediaObject());

        $role->decreaseNumberPeopleInMultimediaObject();
        static::assertEquals(2, $role->getNumberPeopleInMultimediaObject());

        $role->setNumberPeopleInMultimediaObject(3);
        static::assertEquals(3, $role->getNumberPeopleInMultimediaObject());
    }
}
