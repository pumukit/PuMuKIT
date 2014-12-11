<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Repository\MultimediaObjectRepository;

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

        $this->factory->createSeries();

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()));
    }

    public function testCreateMultimediaObject()
    {
        $this->createBroadcasts();

        $series = new Series();
        $this->dm->persist($series);
        $this->dm->flush();

        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
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

    public function testNoDefaultBroadcast()
    {
	$series = $this->factory->createSeries();
	$mmobj = $this->factory->createMultimediaObject($series);

	$this->assertNull($this->mmobjRepo->find($mmobj->getId())->getBroadcast());
    }

    public function createBroadcasts()
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
