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
}
