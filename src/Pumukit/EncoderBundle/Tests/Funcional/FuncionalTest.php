<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class FuncionalTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;
    private $inspectionService;
    private $jobService;
    //private $profileService;
    //private $cpuService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');

        $this->profileService = $kernel->getContainer()->get('pumukitencoder.profile');
        $this->cpuService = $kernel->getContainer()->get('pumukitencoder.cpu');
        $this->inspectionService = $kernel->getContainer()->get('pumukit.inspection');
    }


    public function setUp()
    {
        $this->markTestSkipped('Funcional test not available.');

        $this->vidoeInputPath = realpath(__DIR__.'/../Resources') . '/CAMERA.mp4';

        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
        
        $this->jobService = new JobService($this->dm, $this->profileService, $this->cpuService, $this->inspectionService);
    }

    public function testSimpleEncoding()
    {
        $series = $this->createSeries("series title");
        $mm = $this->createMultimediaObjectAssignedToSeries("mm title", $series);
        $job = $this->jobService->addJob($this->vidoeInputPath, "master_copy", 0, $mm);
        
        dump($job);
        
        $this->jobService->execute($job);

        $this->assertEquals(1, count($mm->getTracks()));
        $this->assertEquals($job->getDuration(), $mm->getDuration());
    }



    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = "Description";
        $duration = 0;

        $mm = new MultimediaObject();

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);

        $mm->setSeries($series);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        return $mm;
    }

    private function createSeries($title)
    {
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime("now");

        $serie = new Series();

        $serie->setTitle($title);
        $serie->setSubtitle($subtitle);
        $serie->setDescription($description);
        $serie->setPublicDate($test_date);

        $this->dm->persist($serie);
        $this->dm->flush();

        return $serie;
    }

}
