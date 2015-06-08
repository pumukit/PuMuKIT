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
        $date1 = new \DateTime("now");
        $date2 = new \DateTime("now");
        $date3 = new \DateTime("now");
        $date4 = new \DateTime("now");

        $duration1 = 30;
        $duration2 = 60;
        $duration3 = 40;
        $duration4 = 120;

        $date1->add(new \DateInterval('P3D'));
        $date2->add(new \DateInterval('P15D'));
        $date3->add(new \DateInterval('P10D'));
        $date4->sub(new \DateInterval('PT60M'));

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
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(1)->toArray()));

        $events = array($event4, $event1);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(2)->toArray()));

        $events = array($event4, $event1, $event3);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(3)->toArray()));

        $events = array($event4, $event1, $event3, $event2);
        $this->assertEquals($events, array_values($this->repo->findFutureAndNotFinished(4)->toArray()));
    }
}
