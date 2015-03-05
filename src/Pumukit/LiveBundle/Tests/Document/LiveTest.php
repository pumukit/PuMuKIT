<?php

namespace Pumukit\LiveBundle\Tests\Document;

use Pumukit\LiveBundle\Document\Live;

class LiveTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $url = 'http://www.pumukit2.com/liveo1';
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
        $locale = 'es';
        $name = 'liveo 1';
        $description = 'canal de liveo';

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
        $this->assertEquals($description, $liveo->getDescription($liveo->getLocale()));
    }
}
