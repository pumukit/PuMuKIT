<?php

namespace Pumukit\LiveBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\LiveBundle\Document\Event;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Pic;

/**
 * @internal
 * @coversNothing
 */
class EventTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $live = new Live();
        $name = 'event name';
        $place = 'event place';
        $date = new \DateTime();
        $duration = '60';
        $display = 0;
        $create_serial = 0;
        $locale = 'en';
        $schedule = ['date' => $date, 'duration' => $duration];

        $pic = new Pic();
        $imagePath = '/path/to/image.jpg';
        $pic->setPath($imagePath);

        $event = new Event();

        $event->setLive($live);
        $event->setName($name);
        $event->setPlace($place);
        $event->setDate($date);
        $event->setDuration($duration);
        $event->setDisplay($display);
        $event->setCreateSerial($create_serial);
        $event->setPic($pic);
        $event->setLocale($locale);
        $event->setSchedule($schedule);

        $this->assertEquals($live, $event->getLive());
        $this->assertEquals($name, $event->getName());
        $this->assertEquals($place, $event->getPlace());
        $this->assertEquals($date, $event->getDate());
        $this->assertEquals($duration, $event->getDuration());
        $this->assertEquals($display, $event->getDisplay());
        $this->assertEquals($create_serial, $event->getCreateSerial());
        $this->assertEquals($locale, $event->getLocale());
        $this->assertEquals($pic, $event->getPic());
        $this->assertEquals($schedule, $event->getSchedule());
    }
}
