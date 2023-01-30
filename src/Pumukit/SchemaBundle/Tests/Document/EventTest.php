<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Pic;

/**
 * @internal
 *
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
        $duration = 60;
        $display = false;
        $create_serial = false;
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

        static::assertEquals($live, $event->getLive());
        static::assertEquals($name, $event->getName());
        static::assertEquals($place, $event->getPlace());
        static::assertEquals($date, $event->getDate());
        static::assertEquals($duration, $event->getDuration());
        static::assertEquals($display, $event->getDisplay());
        static::assertEquals($create_serial, $event->getCreateSerial());
        static::assertEquals($locale, $event->getLocale());
        static::assertEquals($pic, $event->getPic());
        static::assertEquals($schedule, $event->getSchedule());
    }
}
