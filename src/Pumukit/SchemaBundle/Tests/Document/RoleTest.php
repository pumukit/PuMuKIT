<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testDefaults()
    {
        $role = new Role();

        $this->assertEquals('0', $role->getCod());
        $this->assertTrue($role->getDisplay());
        $this->assertEquals(0, $role->getNumberPeopleInMultimediaObject());
        $this->assertEquals($role, $role->cloneResource());
    }

    public function testGetterAndSetter()
    {
        $role = new Role();

        $locale = 'en';
        $cod = 'rol1'; //String - max length = 5
        $xml = 'string <xml>';
        $display = true;
        $name1 = 'Presenter';
        $name2 = null;
        $text1 = 'Presenter Role 1';
        $text2 = null;

        $role->setLocale($locale);
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name1);
        $role->setText($text1);

        $this->assertEquals($locale, $role->getLocale());
        $this->assertEquals($cod, $role->getCod());
        $this->assertEquals($xml, $role->getXml());
        $this->assertEquals($display, $role->getDisplay());
        $this->assertEquals($name1, $role->getName());
        $this->assertEquals($text1, $role->getText());

        $role->setName($name2);
        $role->setText($text2);

        $this->assertEquals($name2, $role->getName());
        $this->assertEquals($text2, $role->getText());

        $nameEs = 'Presentador';
        $textEs = 'Rol de presentador 1';

        $i18nName = ['en' => $name1, 'es' => $nameEs];
        $i18nText = ['en' => $text1, 'es' => $textEs];

        $role->setI18nName($i18nName);
        $role->setI18nText($i18nText);

        $this->assertEquals($i18nName, $role->getI18nName());
        $this->assertEquals($i18nText, $role->getI18nText());
    }

    public function testNumberPeopleInMultimediaObject()
    {
        $role = new Role();

        $this->assertEquals(0, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        $this->assertEquals(1, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        $role->increaseNumberPeopleInMultimediaObject();
        $this->assertEquals(3, $role->getNumberPeopleInMultimediaObject());

        $role->decreaseNumberPeopleInMultimediaObject();
        $this->assertEquals(2, $role->getNumberPeopleInMultimediaObject());

        $role->setNumberPeopleInMultimediaObject(3);
        $this->assertEquals(3, $role->getNumberPeopleInMultimediaObject());
    }
}
