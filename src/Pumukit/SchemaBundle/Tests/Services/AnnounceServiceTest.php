<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;

class AnnounceServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $announceService;
    private $factoryService;
    private $tagService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->seriesRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:Series');
        $this->mmobjRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $this->announceService = $kernel->getContainer()
          ->get('pumukitschema.announce');
        $this->factoryService = $kernel->getContainer()
          ->get('pumukitschema.factory');
        $this->tagService = $kernel->getContainer()
          ->get('pumukitschema.tag');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
          ->remove(array());
        $this->dm->flush();
    }

    public function testGetLast()
    {
        $series1 = $this->factoryService->createSeries();
        $series2 = $this->factoryService->createSeries();

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->flush();

        $tag = new Tag();
        $tag->setCod('PUDENEW');
        $tag->setTitle('PUDENEW');
        $this->dm->persist($tag);
        $this->dm->flush();

        $this->tagService->addTagToMultimediaObject($mm11, $tag->getId());

        $this->assertEquals(array(), $this->announceService->getLast());
    }
}