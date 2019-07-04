<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class LinkServiceTest extends WebTestCase
{
    private $dm;
    private $repoMmobj;
    private $linkService;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repoMmobj = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->linkService = static::$kernel->getContainer()
            ->get('pumukitschema.link')
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repoMmobj = null;
        $this->linkService = null;
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testAddLinkToMultimediaObject()
    {
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
        $mm = $this->linkService->updateLinkInMultimediaObject($mm, $link);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals($newName, $mm->getLinkById($link->getId())->getName());
    }

    public function testRemoveLinkFromMultimediaObject()
    {
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

        $mm = $this->linkService->removeLinkFromMultimediaObject($mm, $link1->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getLinks()));

        $mm = $this->linkService->removeLinkFromMultimediaObject($mm, $link2->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(0, count($mm->getLinks()));
    }

    public function testUpAndDownLinkInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $link1 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link1);

        $link2 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link2);

        $link3 = new Link();
        $mm = $this->linkService->addLinkToMultimediaObject($mm, $link3);

        $links = [$link1, $link2, $link3];
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());

        $this->linkService->upLinkInMultimediaObject($mm, $link2->getId());
        $links = [$link2, $link1, $link3];
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());
        $this->linkService->upLinkInMultimediaObject($mm, $link3->getId());
        $links = [$link2, $link3, $link1];
        $this->assertEquals($links, $mm->getLinks()->toArray());

        $mm = $this->repoMmobj->find($mm->getId());
        $this->linkService->downLinkInMultimediaObject($mm, $link2->getId());
        $links = [$link3, $link2, $link1];
        $this->assertEquals($links, $mm->getLinks()->toArray());
    }
}
