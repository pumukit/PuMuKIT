<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;

class FactoryServiceTest extends WebTestCase
{
    private $dm;
    private $translator;
    private $locales;
    private $factory;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
      ->get('doctrine_mongodb')->getManager();
        $this->translator = $kernel->getContainer()
      ->get('translator');
        $this->locales = $kernel->getContainer()
          ->get('pumukitschema.schema.locale');
        $this->factory = $kernel->getContainer()
      ->get('pumukitschema.factory');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
      ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
      ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
      ->remove(array());
        $this->dm->flush();
    }

    public function testCreateSeries()
    {
        $this->createBroadcasts();

        $this->factory->createSeries();

        $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:Series')));
        $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')));
    }

    public function testCreateMultimediaObject()
    {
        $this->createBroadcasts();

        $series = new Series();
        $this->dm->persist($series);
        $this->dm->flush();

        $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:Series')));
        $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')));
        $this->assertEquals($series, $this->dm->getRepository('PumukitSchemaBundle:Series')->findAll()[0]);
    }

    public function createBroadcasts()
    {
        $broadcastPrivate = new Broadcast();
        $broadcastPrivate->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        $broadcastPrivate->setDefaultSel(true);
        $broadcastPrivate->setName('Private');

        $broadcastPublic = new Broadcast();
        $broadcastPublic->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcastPublic->setDefaultSel(false);
        $broadcastPublic->setName('Public');

        $broadcastCorporative = new Broadcast();
        $broadcastCorporative->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_COR);
        $broadcastCorporative->setDefaultSel(false);
        $broadcastCorporative->setName('Corporative');

        $this->dm->persist($broadcastPrivate);
        $this->dm->persist($broadcastPublic);
        $this->dm->persist($broadcastCorporative);
        $this->dm->flush();
    }
}
