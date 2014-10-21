<?php

namespace Pumukit\DirectBundle\Tests\Document;

class DirectTest extends \PHPUnit_Framework_TestCase
{

  public function testGetterAndSetter()
  {
    $url = 'http://www.pumukit2.com/directo1';
    $passwd = 'password';
    $direct_type_id = Direct::DIRECT_TYPE_FMS;
    $resolution_width = 640;
    $resolution_height = 480;
    $qualities = 'high';
    $ip_source = '127.0.0.1';
    $source_name = 'localhost';
    $index_play = 1;
    $broadcasting = 1;
    $debug = 1;
    $locale = 'es';
    $name = 'directo 1';
    $description = 'canal de directo';
    
    $directo = new Direct();
    
    $directo->setUrl($url);
    $directo->setPasswd($passwd);
    $directo->setDirectTypeId($direct_type_id);
    $directo->setResolutionWidth($resolution_width);
    $directo->setResolutionHeight($resolution_height);
    $directo->setQualities($qualities);
    $directo->setIpSource($ip_source);
    $directo->setSourceName($source_name);
    $directo->setIndexPlay($index_play);
    $directo->setBroadcasting($broadcasting);
    $directo->setDebug($debug);
    $directo->setLocale($locale);
    $directo->setName($name, $locale);
    $directo->setDescription($description, $locale);

    $this->assertEquals($url, $directo->getUrl());
    $this->assertEquals($passwd, $directo->getPasswd());
    $this->assertEquals($direct_type_id, $directo->getDirectTypeId());
    $this->assertEquals($resolution_width, $directo->getResolutionWidth());
    $this->assertEquals($resolution_height, $directo->getResolutionHeight());
    $this->assertEquals($qualities, $directo->getQualities());
    $this->assertEquals($ip_source, $directo->getIpSource());
    $this->assertEquals($source_name, $directo->getSourceName());
    $this->assertEquals($index_play, $directo->getIndexPlay());
    $this->assertEquals($broadcasting, $directo->getBroadcasting());
    $this->assertEquals($debug, $directo->getDebug());
    $this->assertEquals($locale, $directo->getLocale());
    $this->assertEquals($name, $directo->getName($directo->getLocale()));
    $this->assertEquals($description, $directo->getDescription($directo->getLocale()));
  }

}