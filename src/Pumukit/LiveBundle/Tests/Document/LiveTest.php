<?php

namespace Pumukit\LiveBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\LiveBundle\Document\Live;

/**
 * @internal
 * @coversNothing
 */
class LiveTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $url = 'http://www.pumukit.com/liveo1';
        $passwd = 'password';
        $live_type = Live::LIVE_TYPE_FMS;
        $width = 640;
        $height = 480;
        $qualities = 'high';
        $ip_source = '127.0.0.1';
        $source_name = 'localhost';
        $index_play = 1;
        $broadcasting = 1;
        $debug = 1;
        $locale = 'en';
        $name = 'liveo 1';
        $description = 'liveo channel';
        $resolution = ['width' => $width, 'height' => $height];

        $liveo = new Live();

        $liveo->setUrl($url);
        $liveo->setPasswd($passwd);
        $liveo->setLiveType($live_type);
        $liveo->setWidth($width);
        $liveo->setHeight($height);
        $liveo->setQualities($qualities);
        $liveo->setIpSource($ip_source);
        $liveo->setSourceName($source_name);
        $liveo->setIndexPlay($index_play);
        $liveo->setBroadcasting($broadcasting);
        $liveo->setDebug($debug);
        $liveo->setLocale($locale);
        $liveo->setName($name, $locale);
        $liveo->setDescription($description, $locale);
        $liveo->setResolution($resolution);

        $this->assertEquals($url, $liveo->getUrl());
        $this->assertEquals($passwd, $liveo->getPasswd());
        $this->assertEquals($live_type, $liveo->getLiveType());
        $this->assertEquals($width, $liveo->getWidth());
        $this->assertEquals($height, $liveo->getHeight());
        $this->assertEquals($qualities, $liveo->getQualities());
        $this->assertEquals($ip_source, $liveo->getIpSource());
        $this->assertEquals($source_name, $liveo->getSourceName());
        $this->assertEquals($index_play, $liveo->getIndexPlay());
        $this->assertEquals($broadcasting, $liveo->getBroadcasting());
        $this->assertEquals($debug, $liveo->getDebug());
        $this->assertEquals($locale, $liveo->getLocale());
        $this->assertEquals($name, $liveo->getName($liveo->getLocale()));
        $this->assertEquals($name, $liveo->getName());
        $this->assertEquals($description, $liveo->getDescription($liveo->getLocale()));
        $this->assertEquals($description, $liveo->getDescription());
        $this->assertEquals($resolution, $liveo->getResolution());

        $liveo->setDescription($description);
        $this->assertEquals($description, $liveo->getDescription($liveo->getLocale()));

        $nameEs = 'directo 1';
        $i18nName = ['en' => $name, 'es' => $nameEs];
        $liveo->setI18nName($i18nName);
        $this->assertEquals($i18nName, $liveo->getI18nName());

        $descriptionEs = 'canal de directos';
        $i18nDescription = ['en' => $description, 'es' => $descriptionEs];
        $liveo->setI18nDescription($i18nDescription);
        $this->assertEquals($i18nDescription, $liveo->getI18nDescription());

        $name = null;
        $liveo->setName($name, $locale);
        $this->assertEquals(null, $liveo->getName($liveo->getLocale()));

        $description = null;
        $liveo->setDescription($description, $locale);
        $this->assertEquals(null, $liveo->getDescription($liveo->getLocale()));
    }

    public function testCloneResource()
    {
        $live = new Live();

        $this->assertEquals($live, $live->cloneResource());
    }

    public function testToString()
    {
        $live = new Live();

        $this->assertEquals($live->getName(), $live->__toString());
    }

    public function testIsValidLiveType()
    {
        $live = new Live();

        $live_type = Live::LIVE_TYPE_FMS;
        $live->setLiveType($live_type);

        $this->assertTrue($live->isValidLiveType());
    }
}
