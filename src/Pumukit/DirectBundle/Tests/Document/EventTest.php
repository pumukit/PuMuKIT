<?php

namespace Pumukit\DirectBundle\Tests\Document;

class DirectTest extends \PHPUnit_Framework_TestCase
{

  public function testGetterAndSetter()
  {
    $directo = new Direct();
    $direct_id = $directo->getId();
    $name = 'event name';
    $place = 'event place';
    $date = new Timestamp();
    $duration = '60';
    $display = 0;
    $create_serial = 0;
    
    $event = new Event();
    
    $event->setDirectId($direct_id);
    $event->setName($name);
    $event->setPlace($place);
    $event->setDate($date);
    $event->setDuration($duration);
    $event->setDisplay($display);
    $event->setCreateSerial($create_serial);

    $this->assertEquals($url, $event->getUrl());
    $this->assertEquals($name, $event->getName());
    $this->assertEquals($place, $event->getPlace());
    $this->assertEquals($date, $event->getDate());
    $this->assertEquals($duration, $event->getDuration());
    $this->assertEquals($display, $event->getDisplay());
    $this->assertEquals($create_serial, $event->getCreateSerial());
  }

}