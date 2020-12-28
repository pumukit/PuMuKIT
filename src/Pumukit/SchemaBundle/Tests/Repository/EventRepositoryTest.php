<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;

/**
 * @internal
 * @coversNothing
 */
class EventRepositoryTest extends PumukitTestCase
{
    private $repo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Event::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function testRepository(): void
    {
        $live = new Live();
        $this->dm->persist($live);
        $this->dm->flush();

        $locale = 'en';
        $name = 'event name';
        $place = 'event place';
        $date = new \DateTime('now');
        $duration = 60;
        $display = true;
        $create_serial = false;

        $event = new Event();

        $event->setLocale($locale);
        $event->setLive($live);
        $event->setName($name);
        $event->setPlace($place);
        $event->setDate($date);
        $event->setDuration($duration);
        $event->setDisplay($display);
        $event->setCreateSerial($create_serial);

        $this->dm->persist($event);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());
    }

    public function testFindFutureAndNotFinished(): void
    {
        $live1 = new Live();
        $live2 = new Live();
        $this->dm->persist($live1);
        $this->dm->persist($live2);
        $this->dm->flush();

        $date = new \DateTime('15-12-2015 9:00:00');
        $date1 = new \DateTime('18-12-2015 9:00:00');
        $date2 = new \DateTime('30-12-2015 9:00:00');
        $date3 = new \DateTime('25-12-2015 9:00:00');
        $date4 = new \DateTime('15-12-2015 8:00:00');

        $date->setTime(9, 0, 0);
        $date1->setTime(9, 0, 0);
        $date2->setTime(9, 0, 0);
        $date3->setTime(9, 0, 0);
        $date4->setTime(9, 0, 0);

        $duration1 = 30;
        $duration2 = 60;
        $duration3 = 40;
        $duration4 = 120;

        $event1 = new Event();
        $event1->setDisplay(true);
        $event1->setDate($date1);
        $event1->setDuration($duration1);
        $event1->setLive($live1);

        $event2 = new Event();
        $event2->setDisplay(true);
        $event2->setDate($date2);
        $event2->setDuration($duration2);
        $event2->setLive($live1);

        $event3 = new Event();
        $event3->setDisplay(true);
        $event3->setDate($date3);
        $event3->setDuration($duration3);
        $event3->setLive($live2);

        $event4 = new Event();
        $event4->setDisplay(true);
        $event4->setDate($date4);
        $event4->setDuration($duration4);
        $event4->setLive($live2);

        $this->dm->persist($event1);
        $this->dm->persist($event2);
        $this->dm->persist($event3);
        $this->dm->persist($event4);
        $this->dm->flush();

        $events = [$event4];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(1, $date)->toArray()));

        $events = [$event4, $event1];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(2, $date)->toArray()));

        $events = [$event4, $event1, $event3];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(3, $date)->toArray()));

        $events = [$event4, $event1, $event3, $event2];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(4, $date)->toArray()));

        $events = [$event1, $event2];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(4, $date, $live1)->toArray()));

        $events = [$event4, $event3];
        static::assertEquals($events, array_values($this->repo->findFutureAndNotFinished(4, $date, $live2)->toArray()));
    }

    public function testFindByHoursEvent(): void
    {
        $date = new \DateTime('15-12-2015 9:00:00');
        $date1 = new \DateTime('18-12-2015 9:00:00');
        $date2 = new \DateTime('30-12-2015 9:00:00');
        $date3 = new \DateTime('25-12-2015 9:00:00');
        $date4 = new \DateTime('15-12-2015 8:00:00');

        $date->setTime(9, 0, 0);
        $date1->setTime(9, 0, 0);
        $date2->setTime(9, 0, 0);
        $date3->setTime(9, 0, 0);
        $date4->setTime(9, 0, 0);

        $duration1 = 30;
        $duration2 = 60;
        $duration3 = 40;
        $duration4 = 120;

        $event1 = new Event();
        $event1->setDisplay(true);
        $event1->setDate($date1);
        $event1->setDuration($duration1);

        $event2 = new Event();
        $event2->setDisplay(true);
        $event2->setDate($date2);
        $event2->setDuration($duration2);

        $event3 = new Event();
        $event3->setDisplay(true);
        $event3->setDate($date3);
        $event3->setDuration($duration3);

        $event4 = new Event();
        $event4->setDisplay(true);
        $event4->setDate($date4);
        $event4->setDuration($duration4);

        $this->dm->persist($event1);
        $this->dm->persist($event2);
        $this->dm->persist($event3);
        $this->dm->persist($event4);
        $this->dm->flush();

        static::assertEquals($event4, $this->repo->findOneByHoursEvent(3, $date));
    }

    public function testFindCurrentEvents(): void
    {
        static::assertCount(0, $this->repo->findCurrentEvents());

        $this->createEvent('PAST', new \DateTime('-3 minute'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $this->createEvent('LONG PAST', new \DateTime('yesterday'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $this->createEvent('FUTURE', new \DateTime('+1 minute'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $this->createEvent('LONG FUTURE', new \DateTime('tomorrow'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $this->createEvent('ONE', new \DateTime('1 minute ago'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(1, $events);
        static::assertEquals('ONE', $events->current()->getName());

        $this->createEvent('TWO', new \DateTime('2 minute ago'), 4);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(2, $events);

        $this->createEvent('THREE', new \DateTime('3 minute ago'), 6);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(3, $events);

        $events = $this->repo->findCurrentEvents(1);
        static::assertCount(1, $events);
    }

    public function testFindCurrentEventsWithMargin(): void
    {
        static::assertCount(0, $this->repo->findCurrentEvents());

        $this->createEvent('ONE', new \DateTime('+1 minute'), 2);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $events = $this->repo->findCurrentEvents(null, 2);
        static::assertCount(1, $events);

        $events = $this->repo->findCurrentEvents(null, 22);
        static::assertCount(1, $events);

        $this->createEvent('ONE', new \DateTime('-2 minute'), 1);
        $events = $this->repo->findCurrentEvents();
        static::assertCount(0, $events);

        $events = $this->repo->findCurrentEvents(null, 0, 1);
        static::assertCount(1, $events);

        $events = $this->repo->findCurrentEvents(null, 0, 11);
        static::assertCount(1, $events);

        $events = $this->repo->findCurrentEvents(null, 2, 1);
        static::assertCount(2, $events);
    }

    private function createEvent($name, $datetime, $duration): void
    {
        $event = new Event();
        $event->setName($name);
        $event->setDisplay(true);
        $event->setDate($datetime);
        $event->setDuration($duration);
        $this->dm->persist($event);
        $this->dm->flush();
    }
}
