<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;

class FactoryServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $translator;
    private $factory;
    private $locales;

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
        $this->translator = $kernel->getContainer()
          ->get('translator');
        $this->factory = $kernel->getContainer()
          ->get('pumukitschema.factory');
        $this->locales = $this->factory->getLocales();

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

    public function testCreateSeries()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()[0]->getSeries()));
        //NOTE getMultimediaObjects gives us all multimedia objects in the series except prototype
        $this->assertEquals(0, count($this->seriesRepo->findAll()[0]->getMultimediaObjects()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);

        //NOTE series.multimedia_objects have diferent internal initialized value.
        //$this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        $this->assertEquals($series->getId(), $this->mmobjRepo->findAll()[0]->getSeries()->getId());
        $this->assertEquals(MultimediaObject::STATUS_PROTOTYPE, $this->mmobjRepo->findAll()[0]->getStatus());
    }

    public function testCreateMultimediaObject()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()[0]->getSeries()));
        $this->assertEquals($series->getId(), $this->mmobjRepo->findAll()[0]->getSeries()->getId());
        $this->assertEquals(1, count($this->mmobjRepo->find($mmobj->getId())->getSeries()));
        $this->assertEquals($series->getId(), $this->mmobjRepo->find($mmobj->getId())->getSeries()->getId());

        $this->assertEquals(1, count($this->mmobjRepo->findWithoutPrototype($series)));
        $this->assertEquals(1, count($this->seriesRepo->findAll()[0]->getMultimediaObjects()));
        $this->assertEquals($mmobj, $this->seriesRepo->findAll()[0]->getMultimediaObjects()->toArray()[0]);
    }

    public function testUpdateMultimediaObjectTemplate()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();

        $mmobj = $this->factory->createMultimediaObject($series);

        $mmobjTemplate = $this->mmobjRepo->findPrototype($series);
        foreach ($this->locales as $locale) {
            $keyword = $this->translator->trans('keytest', array(), null, $locale);
            $mmobjTemplate->setKeyword($keyword, $locale);
        }
        $this->dm->persist($mmobjTemplate);

        $mmobj2 = $this->factory->createMultimediaObject($series);
        $this->dm->persist($mmobj2);
        $this->dm->flush();

        foreach ($this->locales as $locale) {
            $this->assertNotEquals($mmobj->getKeyword($locale), $this->mmobjRepo->findPrototype($series)->getKeyword($locale));
            $this->assertEquals($mmobj2->getKeyword($locale), $this->mmobjRepo->findPrototype($series)->getKeyword($locale));
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage There is no default selected broadcast neither public broadcast
     */
    public function testNoDefaultBroadcast()
    {
        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertNull($this->mmobjRepo->find($mmobj->getId())->getBroadcast());

        $this->createBroadcasts();
        $mmobj2 = $this->factory->createMultimediaObject($series);

        $this->assertNotNull($this->mmobjRepo->find($mmobj2->getId())->getBroadcast());
    }

    public function testSeriesType()
    {
        $this->createBroadcasts();

        $series_type1 = new SeriesType();
        $name_type1 = 'Series type 1';
        $series_type1->setName($name_type1);

        $series_type2 = new SeriesType();
        $name_type2 = 'Series type 2';
        $series_type2->setName($name_type2);

        $this->dm->persist($series_type1);
        $this->dm->persist($series_type2);
        $this->dm->flush();

        // TODO this souldn't be in a test. This should be executed when creating the SeriesType
        //Workaround to fix reference method initialization.
        $this->dm->clear(get_class($series_type1));
        $series_type1 = $this->dm->find('PumukitSchemaBundle:SeriesType', $series_type1->getId());
        $series_type2 = $this->dm->find('PumukitSchemaBundle:SeriesType', $series_type2->getId());

        $series1 = $this->factory->createSeries();
        $name1 = "Series 1";
        $series1->setTitle($name1);

        $series2 = $this->factory->createSeries();
        $name2 = "Series 2";
        $series2->setTitle($name2);

        $series3 = $this->factory->createSeries();
        $name3 = "Series 3";
        $series3->setTitle($name3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $series1->setSeriesType($series_type1);
        $series2->setSeriesType($series_type1);
        $series3->setSeriesType($series_type2);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(2, count($series_type1->getSeries()));
        $this->assertEquals(1, count($series_type2->getSeries()));
    }

    public function testGetRoles()
    {
        $role1 = new Role();
        $role1->setCod('role1');

        $role2 = new Role();
        $role2->setCod('role2');

        $role3 = new Role();
        $role3->setCod('role3');

        $this->dm->persist($role1);
        $this->dm->persist($role2);
        $this->dm->persist($role3);
        $this->dm->flush();

        $this->assertEquals(3, count($this->factory->getRoles()));
    }

    public function testFindSeriesById()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();

        $this->assertEquals($series, $this->factory->findSeriesById($series->getId(), null));
    }

    public function testGetParentTags()
    {
        $tag = new Tag();
        $tag->setCod("ROOT");

        $this->dm->persist($tag);
        $this->dm->flush();

        $tagA = new Tag();
        $tagA->setCod("A");
        $tagA->setParent($tag);
        $this->dm->persist($tagA);

        $tagB = new Tag();
        $tagB->setCod("B");
        $tagB->setParent($tag);
        $this->dm->persist($tagB);

        $tagB1 = new Tag();
        $tagB1->setCod("B1");
        $tagB1->setParent($tagB);
        $this->dm->persist($tagB1);

        $tagB2 = new Tag();
        $tagB2->setCod("B2");
        $tagB2->setParent($tagB);
        $this->dm->persist($tagB2);

        $tagB2A = new Tag();
        $tagB2A->setCod("B2A");
        $tagB2A->setParent($tagB2);
        $this->dm->persist($tagB2A);

        $this->dm->flush();

        $this->assertEquals(2, count($this->factory->getParentTags()));
    }

    public function testGetMultimediaObjectTemplate()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();

        $this->assertEquals(MultimediaObject::STATUS_PROTOTYPE, $this->factory->getMultimediaObjectTemplate($series)->getStatus());
    }

    public function testDeleteSeries()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));

        $this->factory->deleteSeries($series);

        $this->assertEquals(0, count($this->seriesRepo->findAll()));
        $this->assertEquals(0, count($this->mmobjRepo->findAll()));
    }

    private function createBroadcasts()
    {
        $locale = 'en';

        $broadcastPrivate = new Broadcast();
        $broadcastPrivate->setLocale($locale);
        $broadcastPrivate->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        $broadcastPrivate->setDefaultSel(true);
        $broadcastPrivate->setName('Private');

        $broadcastPublic = new Broadcast();
        $broadcastPublic->setLocale($locale);
        $broadcastPublic->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcastPublic->setDefaultSel(false);
        $broadcastPublic->setName('Public');

        $broadcastCorporative = new Broadcast();
        $broadcastCorporative->setLocale($locale);
        $broadcastCorporative->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_COR);
        $broadcastCorporative->setDefaultSel(false);
        $broadcastCorporative->setName('Corporative');

        $this->dm->persist($broadcastPrivate);
        $this->dm->persist($broadcastPublic);
        $this->dm->persist($broadcastCorporative);
        $this->dm->flush();
    }
}
