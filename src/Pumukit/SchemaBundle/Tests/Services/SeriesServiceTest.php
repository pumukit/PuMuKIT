<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\SeriesService;

class SeriesServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $seriesService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:Series');
        $this->seriesService = static::$kernel->getContainer()
          ->get('pumukitschema.seriespic');
        $this->seriesDispatcher = static::$kernel->getContainer()
          ->get('pumukitschema.series_dispatcher');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
    }

    public function testResetMagicUrl()
    {
        $series = new Series();

        $this->dm->persist($series);
        $this->dm->flush();

        $secret = $series->getSecret();

        $series = $this->repo->find($series->getId());
        $this->assertEquals($secret, $series->getSecret());

        $seriesService = new SeriesService($this->dm, $this->seriesDispatcher);

        $newSecret = $seriesService->resetMagicUrl($series);

        $this->assertNotEquals($secret, $series->getSecret());
        $this->assertEquals($newSecret, $series->getSecret());
    }
}