<?php

namespace Pumukit\EncoderBundle\Tests\Funcional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bridge\Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Pumukit\EncoderBundle\Document\Job;

class FuncionalTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;
    private $inspectionService;
    private $jobService;
    private $tokenStorage;
    private $trackService;
    private $propService;
    private $videoInputPath;

    public function setUp()
    {
        $this->markTestSkipped('Functional tests not available. (A little better, but still broken)');

        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Job::class);

        $this->profileService = static::$kernel->getContainer()->get('pumukitencoder.profile');
        $this->cpuService = static::$kernel->getContainer()->get('pumukitencoder.cpu');
        $this->inspectionService = static::$kernel->getContainer()->get('pumukit.inspection');
        $this->trackService = static::$kernel->getContainer()->get('pumukitschema.track');
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');
        $this->propService = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');

        $this->videoInputPath = realpath(__DIR__.'/../Resources').'/CAMERA.mp4';

        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                    ->getMock();
        $logger = new Logger('job_service_test_logger');
        $logger->pushHandler(new StreamHandler(realpath(__DIR__.'/../Resources').'/encoder_test.log', Logger::WARNING));
        $this->jobService = new JobService($this->dm, $this->profileService, $this->cpuService,
                                           $this->inspectionService, $dispatcher, $logger,
                                           $this->trackService, $this->tokenStorage, $this->propService,
                                           'test');
    }

    public function testSimpleEncoding()
    {
        $series = $this->createSeries('series title');
        $mm = $this->createMultimediaObjectAssignedToSeries('mm title', $series);
        $job = $this->jobService->addJob($this->videoInputPath, 'master_copy', 0, $mm);

        $this->jobService->execute($job);

        $this->assertEquals(1, count($mm->getTracks()));
        $this->assertEquals($job->getDuration(), $mm->getDuration());
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = 'Description';
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
        $test_date = new \DateTime('now');

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
