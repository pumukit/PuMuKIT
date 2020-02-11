<?php

namespace Pumukit\StatsBundle\Tests\EventListener;

use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\EventListener\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 * @coversNothing
 */
class LogTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;
    private $tokenStorage;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(ViewsLog::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->repo = null;
        $this->factoryService = null;
        $this->tokenStorage = null;
        gc_collect_cycles();
    }

    public function testonMultimediaObjectViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new Log($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent();
        $service->onMultimediaObjectViewed($event);
        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testonMultimediaObjectWithoutTrackViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new Log($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent(false);
        $service->onMultimediaObjectViewed($event);
        $this->assertEquals(1, count($this->repo->findAll()));
    }

    private function createMockRequestStack()
    {
        $request = Request::create('/');
        $requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
        $requestStack->expects($this->once())->method('getMasterRequest')->will($this->returnValue($request));

        return $requestStack;
    }

    private function createEvent($withTrack = true)
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        if ($withTrack) {
            $track = new Track();
            $multimediaObject->addTrack($track);
            $this->dm->persist($multimediaObject);
            $this->dm->flush();
        } else {
            $track = null;
        }

        return new ViewedEvent($multimediaObject, $track);
    }
}
