<?php

namespace Pumukit\StatsBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\StatsBundle\Services\LogService;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class LogServiceTest extends WebTestCase
{
    private $dm;
    private $repo;    
    private $factoryService;
    private $tokenStorage;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitStatsBundle:ViewsLog');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->tokenStorage = $kernel->getContainer()
          ->get('security.token_storage');
        
        $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
        
    }

    private function createMockRequestStack()
    {
        $request = Request::create('/');
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack', array(), array(), '', false);
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

    public function testonMultimediaObjectViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new LogService($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent();
        $service->onMultimediaObjectViewed($event);
        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testonMultimediaObjectWithoutTrackViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new LogService($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent(false);
        $service->onMultimediaObjectViewed($event);
        $this->assertEquals(1, count($this->repo->findAll()));
    }    
}