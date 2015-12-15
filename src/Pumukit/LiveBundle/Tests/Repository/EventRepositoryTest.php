<?php

namespace Pumukit\LiveBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\LiveBundle\Document\Event;

class EventRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitLiveBundle:Event');

        $this->dm->getDocumentCollection('PumukitLiveBundle:Event')->remove(array());
        $this->dm->getDocumentCollection('PumukitLiveBundle:Live')->remove(array());
        $this->dm->flush();
    }

    public function testRepository()
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

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testFindFutureAndNotFinished()
    {
        $date = new \DateTime("15-12-2015 9:00:00");
        $date1 = new \DateTime("18-12-2015 9:00:00");
        $date2 = new \DateTime("30-12-2015 9:00:00");
        $date3 = new \DateTime("25-12-2015 9:00:00");
        $date4 = new \DateTime("15-12-2015 8:00:00");

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

        $events = array($event4);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(1, $date)->toArray()));

        $events = array($event4, $event1);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(2, $date)->toArray()));

        $events = array($event4, $event1, $event3);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(3, $date)->toArray()));

        $events = array($event4, $event1, $event3, $event2);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(4, $date)->toArray()));
    }

    public function testFindByHoursEvent()
    {
        $date = new \DateTime("15-12-2015 9:00:00");
        $date1 = new \DateTime("18-12-2015 9:00:00");
        $date2 = new \DateTime("30-12-2015 9:00:00");
        $date3 = new \DateTime("25-12-2015 9:00:00");
        $date4 = new \DateTime("15-12-2015 8:00:00");

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

        $this->assertEquals($event4, $this->repo->findOneByHoursEvent(3, $date));
    }
}
