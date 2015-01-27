<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
      ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
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
        // getMultimediaObjects gives us all multimedia objects in the series except prototype
        $this->assertEquals(0, count($this->seriesRepo->findAll()[0]->getMultimediaObjects()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
        $this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        $this->assertEquals(MultimediaObject::STATUS_PROTOTYPE, $this->mmobjRepo->findAll()[0]->getStatus());
    }

    public function testCreateMultimediaObject()
    {
        $this->createBroadcasts();

        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        //exit;

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()[0]->getSeries()));
        $this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        $this->assertEquals(1, count($this->mmobjRepo->find($mmobj->getId())->getSeries()));
        $this->assertEquals($series, $this->mmobjRepo->find($mmobj->getId())->getSeries());
        //$this->assertEquals(1, count($this->seriesRepo->findAll()[0]->getMultimediaObjects()));
        //$this->assertEquals($mmobj, $this->seriesRepo->findAll()[0]->getMultimediaObjects()->toArray()[0]);
        var_dump("START---");

        foreach($series->getMultimediaObjects() as $k){
          var_dump($k);
        }
        var_dump(count($series->getMultimediaObjects()));
        var_dump(2);
        var_dump(count($this->mmobjRepo->findWithoutPrototype($series)));

        //exit;
        $this->assertEquals(1, count($this->mmobjRepo->findWithoutPrototype($series)));
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

    /*
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
        $series_type1 = new SeriesType();
        $name_type1 = 'Series type 1';
        $series_type1->setName($name_type1);
        $this->dm->persist($series_type1);

        $series_type2 = new SeriesType();
        $name_type2 = 'Series type 2';
        $series_type2->setName($name_type2);
        $this->dm->persist($series_type2);

        $series1 = $this->factory->createSeries();
        $name1 = "Series 1";
        $series1->setTitle($name1);
        $series1->setSeriesType($series_type1);
        $this->dm->persist($series1);

        $series2 = $this->factory->createSeries();
        $name2 = "Series 2";
        $series2->setTitle($name2);
        $series2->setSeriesType($series_type1);
        $this->dm->persist($series2);

        $series3 = $this->factory->createSeries();
        $name3 = "Series 3";
        $series3->setTitle($name3);
        $series3->setSeriesType($series_type2);
        $this->dm->persist($series3);


        var_dump(count($series_type1->getSeries()));
        var_dump(count($this->dm->getRepository('PumukitSchemaBundle:Series')->findBySeriesType($series_type1)));
        
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
