<?php

namespace Pumukit\OpencastBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\OpencastBundle\Services\ClientService;

class ClientServiceTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $trackService;
    private $factoryService;
    private $resourcesDir;
    private $clientService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        if(!static::$kernel->getContainer()->has('pumukitopencast.client')) {
            $this->markTestSkipped("Opencast is not propertly configured.");
        }

        $this->clientService = static::$kernel->getContainer()->get('pumukitopencast.client');

    }

    public function tearDown()
    {
        $this->clientService = null;
        gc_collect_cycles();
        parent::tearDown();
    }



    public function testGetMediaPackages()
    {
        $this->markTestSkipped(
          'Integration test.'
        );

        $media = $this->clientService->getMediaPackages(0,0,0);
    }
}
