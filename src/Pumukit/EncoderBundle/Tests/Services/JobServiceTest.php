<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 * @coversNothing
 */
class JobServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $jobService;
    private $resourcesDir;
    private $logger;
    private $trackService;
    private $factory;
    private $repoMmobj;
    private $tokenStorage;
    private $propService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->logger = static::$kernel->getContainer()->get('logger');
        $this->trackService = static::$kernel->getContainer()->get('pumukitschema.track');
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->propService = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');

        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();

        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $cpuService = new CpuService($this->getDemoCpus(), $this->dm);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->getMock()
        ;
        $inspectionService = $this->getMockBuilder('Pumukit\InspectionBundle\Services\InspectionServiceInterface')
            ->getMock()
        ;
        $inspectionService->expects($this->any())->method('getDuration')->will($this->returnValue(5));
        $this->resourcesDir = realpath(__DIR__.'/../Resources').'/';
        $this->jobService = new JobService(
            $this->dm,
            $profileService,
            $cpuService,
            $inspectionService,
            $dispatcher,
            $this->logger,
            $this->trackService,
            $this->tokenStorage,
            $this->propService,
            'test',
            null
        );
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->repoMmobj = null;
        $this->logger = null;
        $this->trackService = null;
        $this->tokenStorage = null;
        $this->factory = null;
        $this->resourcesDir = null;
        $this->jobService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testCreateTrackFromLocalHardDrive()
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repo->findAll()));

        $originalFile = $this->resourcesDir.'CAMERA.mp4';

        $filePath = $this->resourcesDir.'CAMERACopy.mp4';
        if (copy($originalFile, $filePath)) {
            $file = new UploadedFile($filePath, 'CAMERA.mp4', null, null, null, true);

            $profile = 'MASTER_COPY';
            $priority = 2;
            $language = 'en';
            $description = [
                'en' => 'local track description',
                'es' => 'descripción del archivo local',
            ];

            $multimediaObject = $this->jobService->createTrackFromLocalHardDrive($multimediaObject, $file, $profile, $priority, $language, $description);

            $this->assertEquals(0, count($multimediaObject->getTracks()));
            $this->assertEquals(1, count($this->repo->findAll()));
        }

        $this->deleteCreatedFiles();
    }

    public function testCreateTrackFromInboxOnServer()
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repo->findAll()));

        $originalFile = $this->resourcesDir.'CAMERA.mp4';

        $filePath = $this->resourcesDir.'CAMERACopy.mp4';
        if (copy($originalFile, $filePath)) {
            $profile = 'MASTER_COPY';
            $priority = 2;
            $language = 'en';
            $description = [
                'en' => 'track description inbox',
                'es' => 'descripción del archivo inbox',
            ];

            $multimediaObject = $this->jobService->createTrackFromInboxOnServer($multimediaObject, $filePath, $profile, $priority, $language, $description);

            $this->assertEquals(0, count($multimediaObject->getTracks()));
            $this->assertEquals(1, count($this->repo->findAll()));
        }

        $this->deleteCreatedFiles();
        unlink($filePath);
    }

    public function testAddJob()
    {
        $profiles = $this->getDemoProfiles();

        $pathFile = $this->resourcesDir.'test.txt';

        $profile = 'MASTER_COPY';
        $priority = 2;
        $language = 'es';
        $description = ['en' => 'test', 'es' => 'prueba'];

        $series = new Series();
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setSeries($series);
        $this->dm->persist($series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->jobService->addJob($pathFile, $profile, $priority, $multimediaObject, $language, $description);

        $this->assertEquals(1, count($this->repo->findAll()));

        $pathFile2 = $this->resourcesDir.'test2.txt';

        $profile2 = 'MASTER_VIDEO_H264';
        $priority2 = 3;
        $language2 = 'en';
        $description2 = ['en' => 'test2', 'es' => 'prueba2'];

        $this->jobService->addJob($pathFile2, $profile2, $priority2, $multimediaObject, $language2, $description2);

        $this->assertEquals(2, count($this->repo->findAll()));
    }

    public function testPauseJob()
    {
        $job = $this->createNewJob();
        $this->jobService->pauseJob($job->getId());

        $this->assertEquals(Job::STATUS_PAUSED, $job->getStatus());
    }

    public function testResumeJob()
    {
        $job = $this->createNewJob();

        $this->jobService->pauseJob($job->getId());
        $this->jobService->resumeJob($job->getId());

        $this->assertEquals(Job::STATUS_WAITING, $job->getStatus());
    }

    public function testCancelJob()
    {
        $job = $this->createNewJob();
        $this->assertEquals(1, count($this->repo->findAll()));
        $this->jobService->cancelJob($job->getId());
        $this->assertEquals([], $this->repo->findAll());

        $job = $this->createNewJob();
        $this->assertEquals(1, count($this->repo->findAll()));
        $this->jobService->pauseJob($job->getId());
        $this->jobService->resumeJob($job->getId());
        $this->jobService->cancelJob($job->getId());
        $this->assertEquals([], $this->repo->findAll());

        $job1 = $this->createNewJob();
        $job2 = $this->createNewJob();
        $this->assertEquals(2, count($this->repo->findAll()));
        $this->jobService->cancelJob($job1->getId());
        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($job2, $this->repo->findAll()[0]);
    }

    public function testGetAllJobsStatus()
    {
        $job1 = $this->createNewJob();
        $job2 = $this->createNewJob();
        $job3 = $this->createNewJob();
        $job4 = $this->createNewJob();
        $job5 = $this->createNewJob();
        $job6 = $this->createNewJob();
        $job7 = $this->createNewJob();
        $job8 = $this->createNewJob(Job::STATUS_PAUSED);
        $job9 = $this->createNewJob(Job::STATUS_PAUSED);
        $job10 = $this->createNewJob(Job::STATUS_EXECUTING);
        $job11 = $this->createNewJob(Job::STATUS_EXECUTING);
        $job12 = $this->createNewJob(Job::STATUS_FINISHED);
        $job13 = $this->createNewJob(Job::STATUS_FINISHED);
        $job14 = $this->createNewJob(Job::STATUS_FINISHED);
        $job15 = $this->createNewJob(Job::STATUS_FINISHED);
        $job16 = $this->createNewJob(Job::STATUS_FINISHED);
        $job17 = $this->createNewJob(Job::STATUS_FINISHED);
        $job18 = $this->createNewJob(Job::STATUS_FINISHED);
        $job19 = $this->createNewJob(Job::STATUS_FINISHED);
        $job20 = $this->createNewJob(Job::STATUS_FINISHED);

        $allJobsStatus = $this->jobService->getAllJobsStatus();

        $this->assertEquals(0, $allJobsStatus['error']);
        $this->assertEquals(2, $allJobsStatus['paused']);
        $this->assertEquals(7, $allJobsStatus['waiting']);
        $this->assertEquals(2, $allJobsStatus['executing']);
        $this->assertEquals(9, $allJobsStatus['finished']);
    }

    public function testGetNextJob()
    {
        $job1 = $this->createNewJob(null, 1, 0);
        $job2 = $this->createNewJob(null, 2, 1);
        $job3 = $this->createNewJob(null, 1, 2);
        $job4 = $this->createNewJob(null, 3, 3);
        $job5 = $this->createNewJob(null, 2, 4);
        $job6 = $this->createNewJob(null, 3, 5);
        $job7 = $this->createNewJob(null, 1, 6);

        $this->assertEquals($job4, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job4->getId());
        $this->assertEquals($job6, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job6->getId());
        $this->assertEquals($job2, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job2->getId());
        $this->assertEquals($job5, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job5->getId());
        $this->assertEquals($job1, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job1->getId());
        $this->assertEquals($job3, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job3->getId());
        $this->assertEquals($job7, $this->jobService->getNextJob());

        $this->jobService->cancelJob($job7->getId());
        $this->assertNull($this->jobService->getNextJob());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't find given profile with name
     */
    public function testExceptionProfileName()
    {
        $pathFile = $this->resourcesDir.'test.txt';

        $profile = 'non_existing';
        $priority = 2;
        $language = 'es';
        $description = ['en' => 'test', 'es' => 'prueba'];

        $multimediaObject = new MultimediaObject();
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->jobService->addJob($pathFile, $profile, $priority, $multimediaObject, $language, $description);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't find job with id
     */
    public function testExceptionJobId()
    {
        $this->jobService->pauseJob('non_existing');
        $this->jobService->resumeJob('non_existing');
        $this->jobService->cancelJob('non_existing');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Trying to cancel job
     */
    public function testExceptionCancelJobNotPausedOrWaiting()
    {
        $job = $this->createNewJob(Job::STATUS_EXECUTING);
        $this->jobService->cancelJob($job->getId());
    }

    public function testGetJobsByMultimediaObjectId()
    {
        $mm_id1 = '54ad3f5e6e4cd68a278b4573';
        $mm_id2 = '54ad3f5e6e4cd68a278b4574';

        $job1 = $this->createNewJob(Job::STATUS_EXECUTING);
        $job2 = $this->createNewJob(Job::STATUS_WAITING);
        $job3 = $this->createNewJob(Job::STATUS_WAITING);

        $job1->setMmId($mm_id1);
        $job2->setMmId($mm_id2);
        $job3->setMmId($mm_id1);

        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->flush();

        $this->assertEquals(2, count($this->jobService->getNotFinishedJobsByMultimediaObjectId($mm_id1)));
        $this->assertEquals(1, count($this->jobService->getNotFinishedJobsByMultimediaObjectId($mm_id2)));
    }

    public function testGetStatusError()
    {
        $this->assertEquals(Job::STATUS_ERROR, $this->jobService->getStatusError());
    }

    private function createNewJob($status = null, $priority = null, $timeadd = 0)
    {
        $job = new Job();
        if (null !== $status) {
            $job->setStatus($status);
        }
        if (null !== $priority) {
            $job->setPriority($priority);
        }
        $datetime = new \DateTime('now');
        $datetime->modify("+{$timeadd} hour");
        $job->setTimeini($datetime);
        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }

    private function getDemoCpus()
    {
        return [
            'CPU_LOCAL' => [
                'id' => 1,
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco1',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_REMOTE' => [
                'id' => 2,
                'host' => '192.168.5.123',
                'max' => 2,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
        ];
    }

    private function getDemoProfiles()
    {
        return [
            'MASTER_COPY' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'resolution_hor' => 0,
                'resolution_ver' => 0,
                'framerate' => '0',
                'channels' => 1,
                'audio' => false,
                'bat' => 'cp "{{input}}" "{{output}}"',
                'streamserver' => [
                    'type' => ProfileService::STREAMSERVER_STORE,
                    'host' => '127.0.0.1',
                    'name' => 'Localmaster',
                    'description' => 'Local masters server',
                    'dir_out' => __DIR__.'/../Resources/dir_out',                                                         ],
                'app' => 'cp',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
            'MASTER_VIDEO_H264' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'format' => 'mp4',
                'codec' => 'h264',
                'mime_type' => 'video/x-mp4',
                'extension' => 'mp4',
                'resolution_hor' => 0,
                'resolution_ver' => 0,
                'bitrate' => '1 Mbps',
                'framerate' => '25/1',
                'channels' => 1,
                'audio' => false,
                'bat' => 'ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 15 -threads 0 "{{output}}"',
                'streamserver' => [
                    'type' => ProfileService::STREAMSERVER_STORE,
                    'host' => '192.168.5.125',
                    'name' => 'Download',
                    'description' => 'Download server',
                    'dir_out' => __DIR__.'/../Resources/dir_out',
                    'url_out' => 'http://localhost:8000/downloads/',
                ],
                'app' => 'ffmpeg',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
        ];
    }

    private function deleteCreatedFiles()
    {
        $mmobjs = $this->repoMmobj->findAll();

        foreach ($mmobjs as $mm) {
            $mmDir = $this->getDemoProfiles()['MASTER_COPY']['streamserver']['dir_out'].'/'.$mm->getSeries()->getId().'/';
            if (is_dir($mmDir)) {
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($mmDir);
            }

            $tmpMmDir = '/tmp/'.$mm->getId().'/';
            if (is_dir($tmpMmDir)) {
                $files = glob($tmpMmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($tmpMmDir);
            }
        }
    }
}
