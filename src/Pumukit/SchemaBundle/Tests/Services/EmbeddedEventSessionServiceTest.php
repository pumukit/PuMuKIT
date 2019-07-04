<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\Series;

class EmbeddedEventSessionServiceTest extends WebTestCase
{
    private $dm;
    private $service;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();

        $this->service = static::$kernel->getContainer()
          ->get('pumukitschema.eventsession');
        $this->factoryService = static::$kernel->getContainer()
          ->get('pumukitschema.factory');

        $this->dm->getDocumentCollection(MultimediaObject::class)
          ->remove([]);
        $this->dm->getDocumentCollection(Series::class)
          ->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->service = null;
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testToday()
    {
        $series = $this->factoryService->createSeries();

        $this->dm->persist($series);
        $this->dm->flush();

        //Today object
        $mm11 = $this->factoryService->createMultimediaObject($series);
        $mm11->setType(MultimediaObject::TYPE_LIVE);
        $event = new EmbeddedEvent();
        $mm11->setEmbeddedEvent($event);

        $start = new \DateTime('-1 minute');
        $end = new \DateTime('+1 minute');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);

        $this->dm->persist($mm11);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(1, count($now));
        $today = $this->service->findEventsToday();
        $this->assertEquals(1, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(0, count($next));

        //Add session yesterday
        $start = new \DateTime('-33 hour');
        $end = new \DateTime('-32 hour');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(1, count($now));
        $today = $this->service->findEventsToday();
        $this->assertEquals(1, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(0, count($next));

        //Add session tomorrow
        $start = new \DateTime('+33 hour');
        $end = new \DateTime('+34 hour');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(1, count($now));
        $today = $this->service->findEventsToday();
        $this->assertEquals(1, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(1, count($next));

        //Other today object
        $mm12 = $this->factoryService->createMultimediaObject($series);
        $mm12->setType(MultimediaObject::TYPE_LIVE);
        $event = new EmbeddedEvent();
        $mm12->setEmbeddedEvent($event);

        $start = new \DateTime('-2 minute');
        $end = new \DateTime('+2 minute');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);

        $this->dm->persist($mm12);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(2, count($now));

        $this->assertTrue(
            $now[0]['data']['session']['start'] <
            $now[1]['data']['session']['start']
        );
        $today = $this->service->findEventsToday();
        $this->assertEquals(2, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(1, count($next));

        //Other future object
        $mm12 = $this->factoryService->createMultimediaObject($series);
        $mm12->setType(MultimediaObject::TYPE_LIVE);
        $event = new EmbeddedEvent();
        $mm12->setEmbeddedEvent($event);

        $start = new \DateTime('+1 year');
        $end = new \DateTime('+1 year +2 minute');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);

        $this->dm->persist($mm12);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(2, count($now));
        $today = $this->service->findEventsToday();
        $this->assertEquals(2, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(2, count($next));

        $this->assertTrue(
            $next[0]['data']['session']['start'] <
            $next[1]['data']['session']['start']
        );

        //Other future object
        $mm12 = $this->factoryService->createMultimediaObject($series);
        $mm12->setType(MultimediaObject::TYPE_LIVE);
        $event = new EmbeddedEvent();
        $mm12->setEmbeddedEvent($event);

        $start = new \DateTime('+1 month');
        $end = new \DateTime('+1 month +2 minute');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        $session = new EmbeddedEventSession();
        $session->setStart($start);
        $session->setEnds($end);
        $session->setDuration($duration);

        $event->addEmbeddedEventSession($session);

        $this->dm->persist($mm12);
        $this->dm->flush();

        $now = $this->service->findEventsNow();
        $this->assertEquals(2, count($now));
        $today = $this->service->findEventsToday();
        $this->assertEquals(2, count($today));
        $next = $this->service->findNextEvents();
        $this->assertEquals(3, count($next));

        $this->assertTrue(
            $next[0]['data']['session']['start'] <
            $next[1]['data']['session']['start']
        );

        $this->assertTrue(
            $next[1]['data']['session']['start'] <
            $next[2]['data']['session']['start']
        );
    }
}
