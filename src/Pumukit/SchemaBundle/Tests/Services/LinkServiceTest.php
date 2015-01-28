<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\File\File;

class LinkServiceTest extends WebTestCase
{
    private $dm;
    private $repoMmobj;
    private $linkService;
    private $factoryService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repoMmobj = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->linkService = $kernel->getContainer()
          ->get('pumukitschema.link');
        $this->factoryService = $kernel->getContainer()
          ->get('pumukitschema.factory');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
        $this->dm->flush();
    }

    public function testAddLinkToMultimediaObject()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getLinks()));

        $link = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link);

        $this->assertEquals(1, count($mm->getLinks()));
    }

    public function testUpdateLinkInMultimediaObject()
    {
        // TODO
    }
}