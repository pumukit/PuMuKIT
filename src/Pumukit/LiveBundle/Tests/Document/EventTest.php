<?php

namespace Pumukit\LiveBundle\Tests\Document;

use Pumukit\LiveBundle\Document\Live;
use Pumukit\LiveBundle\Document\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $live = new Live();
        $name = 'event name';
        $place = 'event place';
        $date = new \DateTime();
        $duration = '60';
        $display = 0;
        $create_serial = 0;

        $event = new Event();

        $event->setLive($live);
        $event->setName($name);
        $event->setPlace($place);
        $event->setDate($date);
        $event->setDuration($duration);
        $event->setDisplay($display);
        $event->setCreateSerial($create_serial);

        $this->assertEquals($live, $event->getLive());
        $this->assertEquals($name, $event->getName());
        $this->assertEquals($place, $event->getPlace());
        $this->assertEquals($date, $event->getDate());
        $this->assertEquals($duration, $event->getDuration());
        $this->assertEquals($display, $event->getDisplay());
        $this->assertEquals($create_serial, $event->getCreateSerial());
    }
}
