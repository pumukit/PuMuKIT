<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectDurationService;

class MultimediaObjectDurationServiceTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $factory;
    private $mmsService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
                           ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
                             ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->factory = static::$kernel->getContainer()
                                ->get('pumukitschema.factory');
        $this->mmsService = static::$kernel->getContainer()
                                   ->get('pumukitschema.mmsduration');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmRepo = null;
        $this->factory = null;
        $this->mmsService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetDuration()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $mm->setDuration(100);

        $duration = $this->mmsService->getMmobjDuration($mm);
        $this->assertEquals(100, $duration);
    }
}
