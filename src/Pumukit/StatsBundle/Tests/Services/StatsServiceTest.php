<?php

namespace Pumukit\StatsBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\StatsBundle\Services\StatsService;
use Pumukit\StatsBundle\Document\ViewsLog;

class StatsServiceTest extends WebTestCase
{
    private $dm;
    private $repo;    
    private $factoryService;

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
        
        $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
            ->remove(array());
    }

    private function logView($when, MultimediaObject $multimediaObject, Track $track = null)
    {
        $log = new ViewsLog('/', '8.8.8.8', 'test', '', $multimediaObject->getId(), $multimediaObject->getSeries()->getId(), null);
        $log->setDate($when);
        
        $this->dm->persist($log);
        $this->dm->flush();        
        return $log;
    }
    
    private function initContext()
    {
        $series = $this->factoryService->createSeries();
        $list = array();
        $list[1] = $this->factoryService->createMultimediaObject($series);
        $list[2] = $this->factoryService->createMultimediaObject($series);
        $list[3] = $this->factoryService->createMultimediaObject($series);
        $list[4] = $this->factoryService->createMultimediaObject($series);
        $list[5] = $this->factoryService->createMultimediaObject($series);

        foreach($list as $i => $mm) {
            $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
            $this->dm->persist($mm);            
        }
        $this->dm->flush();        
        
        
        $this->logView(new \DateTime('now'), $list[1]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[2]);
        $this->logView(new \DateTime('now'), $list[2]);        
        
        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);
        
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);

        return $list;
    }

    private function initTags($list)
    {
        $globalTag = new Tag();
        $globalTag->setCod('tv');
        $this->dm->persist($globalTag);

        $tags = array();
        foreach($list as $i => $mm) {
            $tag = new Tag();
            $tag->setCod($i);
            $this->dm->persist($tag);
            $tags[$i] = $tag;
        }
        $this->dm->flush();

        foreach($list as $i => $mm) {
            $mm->addTag($globalTag);
            $mm->addTag($tags[$i]);
            $this->dm->persist($mm);            
        }
        $this->dm->flush();        
    }

    public function testSimpleStatsService()
    {
        $list = $this->initContext();

        $service = new StatsService($this->dm);
        $mv = $service->getMostViewed(array(), 1, 1);
        $this->assertEquals(1, count($mv));
        $this->assertEquals($mv, array($list[3]));

        $mv = $service->getMostViewed(array(), 30, 1);
        $this->assertEquals($mv, array($list[5]));

        $mv = $service->getMostViewed(array(), 1, 3);
        $this->assertEquals($mv, array($list[3], $list[2], $list[1]));

        $mv = $service->getMostViewed(array(), 30, 3);
        $this->assertEquals($mv, array($list[5], $list[4], $list[3]));

        $mv = $service->getMostViewed(array(), 30, 30);
        $this->assertEquals(5, count($mv));
        $this->assertEquals($mv, array($list[5], $list[4], $list[3], $list[2], $list[1]));        
    }

    public function testStatsServiceWithBlockedVideos()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $service = new StatsService($this->dm);
        $mv = $service->getMostViewed(array('tv'), 30, 3);
        $this->assertEquals($mv, array($list[5], $list[4], $list[3]));

        $mm = $list[5];
        foreach($mm->getTags() as $tag) {
          $mm->removeTag($tag);
        }
        $this->dm->persist($mm);
        $this->dm->flush();        

        $mv = $service->getMostViewed(array('tv'), 30, 3);
        $this->assertEquals($mv, array($list[4], $list[3], $list[2]));
    }

    public function testStatsServiceWithTags()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $service = new StatsService($this->dm);

        $mv = $service->getMostViewed(array('1'), 30, 30);
        $this->assertEquals($mv, array($list[1]));

        $mv = $service->getMostViewed(array('11'), 30, 30);
        $this->assertEquals($mv, array());        

        $mv = $service->getMostViewed(array('1'), 1, 3);
        $this->assertEquals($mv, array($list[1]));        
    }

    public function testStatsServiceUsingFilters()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $filter = $this->dm->getFilterCollection()->enable('frontend');
        $filter->setParameter('pub_channel_tag', '1');
        $filter->setParameter('private_broadcast', array('$nin' => array('1', '2', '3')));

        $filter = $this->dm->getFilterCollection()->enable('channel');
        $filter->setParameter('channel_tag', '1');

        $service = new StatsService($this->dm);

        $mv = $service->getMostViewedUsingFilters(30, 30);
        $this->assertEquals($mv, array($list[1]));        
    }
    
}
