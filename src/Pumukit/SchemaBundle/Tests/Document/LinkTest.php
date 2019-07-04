<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $name = 'name';
        $locale = 'en';

        $link = new Link();
        $link->setName($name);

        $this->assertEquals($name, $link->getName());

        $nameEs = 'nombre';
        $localeEs = 'es';

        $nameI18n = [$locale => $name, $localeEs => $nameEs];

        $link->setI18nName($nameI18n);

        $this->assertEquals($nameI18n, $link->getI18nName());

        $name = null;

        $link->setName($name);
        $this->assertEquals($name, $link->getName());
    }
}
