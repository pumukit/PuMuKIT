<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Role;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $role = new Role();

        $this->assertEquals('0', $role->getCod());
        $this->assertTrue($role->getDisplay());
        $this->assertEquals(0, $role->getNumberPeopleInMultimediaObject());
    }

    public function testGetterAndSetter()
    {
        $role = new Role();

        $locale = 'en';
        $cod = 'rol1'; //String - max length = 5
        $xml = 'string <xml>';
        $display = true;
        $name = 'Presenter';
        $text = 'Presenter Role 1';

        $role->setLocale($locale);
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name);
        $role->setText($text);

        $this->assertEquals($locale, $role->getLocale());
        $this->assertEquals($cod, $role->getCod());
        $this->assertEquals($xml, $role->getXml());
        $this->assertEquals($display, $role->getDisplay());
        $this->assertEquals($name, $role->getName());
        $this->assertEquals($text, $role->getText());

        $nameEs = 'Presentador';
        $textEs = 'Rol de presentador 1';

        $i18nName = array('en' => $name, 'es' => $nameEs);
        $i18nText = array('en' => $text, 'es' => $textEs);

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
    }
}
