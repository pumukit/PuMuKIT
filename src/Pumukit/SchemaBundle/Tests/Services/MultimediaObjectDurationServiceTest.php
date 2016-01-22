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

    public function __construct()
    {

        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
                           ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
                             ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->factory = $kernel->getContainer()
                                ->get('pumukitschema.factory');
        $this->mmsService = $kernel->getContainer()
                                   ->get('pumukitschema.mmsduration');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->flush();
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
