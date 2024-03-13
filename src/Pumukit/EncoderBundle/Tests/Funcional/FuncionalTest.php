<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Funcional;

use Monolog\Handler\StreamHandler;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * TODO: DIGIREPO REMOVE
 *
 * @coversNothing
 */
class FuncionalTest extends PumukitTestCase
{
    private $repo;
    private $profileService;
    private $cpuService;
    private $inspectionService;
    //    private $jobService;
    private $tokenStorage;
    private $trackService;
    private $propService;
    private $videoInputPath;

    public function setUp(): void
    {
        static::markTestSkipped('Functional tests not available. (A little better, but still broken)');

        /*$options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Job::class);

        $this->profileService = static::$kernel->getContainer()->get('pumukitencoder.profile');
        $this->cpuService = static::$kernel->getContainer()->get('pumukitencoder.cpu');
        $this->inspectionService = static::$kernel->getContainer()->get('pumukit.inspection');
        $this->trackService = static::$kernel->getContainer()->get('pumukitschema.track');
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');
        $this->propService = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');

        $this->videoInputPath = realpath(__DIR__.'/../Resources').'/CAMERA.mp4';

        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $logger = new Logger('job_service_test_logger');
        $logger->pushHandler(new StreamHandler(realpath(__DIR__.'/../Resources').'/encoder_test.log', Logger::WARNING));
        $this->jobService = new JobService(
            $this->dm,
            $this->profileService,
            $this->cpuService,
            $this->inspectionService,
            $dispatcher,
            $logger,
            $this->trackService,
            $this->tokenStorage,
            $this->propService,
            'test'
        );*/
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->profileService = null;
        $this->cpuService = null;
        $this->inspectionService = null;
        //        $this->jobService = null;
        $this->tokenStorage = null;
        $this->trackService = null;
        $this->propService = null;
        $this->videoInputPath = null;
        gc_collect_cycles();
    }

    public function testSimpleEncoding()
    {
        $series = $this->createSeries('series title');
        $mm = $this->createMultimediaObjectAssignedToSeries('mm title', $series);

        // TODO: DIGIREPO REMOVE
        //        $job = $this->jobService->addJob($this->videoInputPath, 'master_copy', 0, $mm);

        //        $this->jobService->execute($job);

        static::assertCount(1, $mm->getTracks());
        //        static::assertEquals($job->getDuration(), $mm->getDuration());
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
