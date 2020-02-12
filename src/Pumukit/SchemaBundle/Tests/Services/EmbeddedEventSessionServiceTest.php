<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
class EmbeddedEventSessionServiceTest extends PumukitTestCase
{
    private $service;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->service = static::$kernel->getContainer()->get('pumukitschema.eventsession');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->service = null;
        $this->factoryService = null;
        gc_collect_cycles();
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
        $this->assertCount(1, $now);
        $today = $this->service->findEventsToday();
        $this->assertCount(1, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(0, $next);

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
        $this->assertCount(1, $now);
        $today = $this->service->findEventsToday();
        $this->assertCount(1, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(0, $next);

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
        $this->assertCount(1, $now);
        $today = $this->service->findEventsToday();
        $this->assertCount(1, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(1, $next);

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
        $this->assertCount(2, $now);

        $this->assertTrue(
            $now[0]['data']['session']['start'] <
            $now[1]['data']['session']['start']
        );
        $today = $this->service->findEventsToday();
        $this->assertCount(2, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(1, $next);

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
        $this->assertCount(2, $now);
        $today = $this->service->findEventsToday();
        $this->assertCount(2, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(2, $next);

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
        $this->assertCount(2, $now);
        $today = $this->service->findEventsToday();
        $this->assertCount(2, $today);
        $next = $this->service->findNextEvents();
        $this->assertCount(3, $next);

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
