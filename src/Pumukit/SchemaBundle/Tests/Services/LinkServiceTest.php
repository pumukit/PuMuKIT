<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getLinks()));

        $link = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getLinks()));
    }

    public function testUpdateLinkInMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getLinks()));

        $link = new Link();
        $name = 'Original link name';
        $link->setName($name);
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getLinks()));
        $this->assertEquals($name, $mm->getLinkById($link->getId())->getName());

        $newName = 'New link name';
        $link = $mm->getLinkById($link->getId());
        $link->setName($newName);
        $mm = $this->linkService->updateLinkInMultimediaObject($mm);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals($newName, $mm->getLinkById($link->getId())->getName());
    }

    public function testRemoveLinkFromMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getLinks()));

        $link1 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link1);
        $mm = $this->repoMmobj->find($mm->getId());

        $link2 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link2);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(2, count($mm->getLinks()));

        $mm = $this->linkService->removeLinkFromMultimediaObject($mm, $link1);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getLinks()));

        $mm = $this->linkService->removeLinkFromMultimediaObject($mm, $link2);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(0, count($mm->getLinks()));
    }

    public function testUpAndDownLinkInMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $link1 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link1);

        $link2 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link2);

        $link3 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link3);

        $links = array($link1, $link2, $link3);
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());

        $this->linkService->upLinkInMultimediaObject($mm, $link2);
        $links = array($link2, $link1, $link3);
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());
        $this->linkService->upLinkInMultimediaObject($mm, $link3);
        $links = array($link2, $link3, $link1);
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());
        $this->linkService->downLinkInMultimediaObject($mm, $link2);
        $links = array($link3, $link2, $link1);
        $this->assertEquals($links, $mm->getLinks()->toArray());
        
    }

    private function createBroadcast($broadcastTypeId)
    {
        $broadcast = new Broadcast();
        $broadcast->setName(ucfirst($broadcastTypeId));
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd('password');
        if (0 === strcmp(Broadcast::BROADCAST_TYPE_PRI, $broadcastTypeId)) {
            $broadcast->setDefaultSel(true);
        } else {
            $broadcast->setDefaultSel(false);
        }
        $broadcast->setDescription(ucfirst($broadcastTypeId).' broadcast');

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }
}