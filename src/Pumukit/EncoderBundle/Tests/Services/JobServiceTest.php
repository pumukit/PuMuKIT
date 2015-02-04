<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class JobServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $jobService;
    //private $profileService;
    //private $cpuService;
    private $resourcesDir;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitEncoderBundle:Job');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->flush();
        
        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $cpuService = new CpuService($this->getDemoCpus(), $this->dm);
        $inspectionService = $this->getMock('Pumukit\InspectionBundle\Services\InspectionServiceInterface');
        $inspectionService->expects($this->any())->method('getDuration')->will($this->returnValue(5));
        $this->jobService = new JobService($this->dm, $profileService, $cpuService, $inspectionService, null, true);
        $this->resourcesDir = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR;
    }
    
    public function testAddJob()
    {
        $profiles = $this->getDemoProfiles();

        $pathFile = $this->resourcesDir.'test.txt';

        $profile = 'MASTER_COPY';
        $priority = 2;
        $language = 'es';
        $description = array('en' => 'test', 'es' => 'prueba');

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
        $description2 = array('en' => 'test2', 'es' => 'prueba2');

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
        $this->assertEquals(array(), $this->repo->findAll());

        $job = $this->createNewJob();
        $this->assertEquals(1, count($this->repo->findAll()));
        $this->jobService->pauseJob($job->getId());
        $this->jobService->resumeJob($job->getId());
        $this->jobService->cancelJob($job->getId());
        $this->assertEquals(array(), $this->repo->findAll());

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
        $this->markTestSkipped('S');

        $job1 = $this->createNewJob(null, 1);
        $job2 = $this->createNewJob(null, 2);
        $job3 = $this->createNewJob(null, 1);
        $job4 = $this->createNewJob(null, 3);
        $job5 = $this->createNewJob(null, 2);
        $job6 = $this->createNewJob(null, 3);
        $job7 = $this->createNewJob(null, 1);

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
     * @expectedException Exception
     * @expectedExceptionMessage Can't find given profile with name
     */
    public function testExceptionProfileName()
    {
        $pathFile = $this->resourcesDir.'test.txt';

        $profile = 'non_existing';
        $priority = 2;
        $language = 'es';
        $description = array('en' => 'test', 'es' => 'prueba');

        $multimediaObject = new MultimediaObject();
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->jobService->addJob($pathFile, $profile, $priority, $multimediaObject, $language, $description);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't find job with id
     */
    public function testExceptionJobId()
    {
        $this->jobService->pauseJob('non_existing');
        $this->jobService->resumeJob('non_existing');
        $this->jobService->cancelJob('non_existing');
    }

    /**
     * @expectedException Exception
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

        $this->assertEquals(2, count($this->jobService->getJobsByMultimediaObjectId($mm_id1)));
        $this->assertEquals(1, count($this->jobService->getJobsByMultimediaObjectId($mm_id2)));
    }

    public function testGetStatusError()
    {
        $this->assertEquals(Job::STATUS_ERROR, $this->jobService->getStatusError());
    }

    private function createNewJob($status = null, $priority = null)
    {
        $job = new Job();
        if (null !== $status){
            $job->setStatus($status);
        }
        if (null !== $priority){
            $job->setPriority($priority);
        }
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }

    private function getDemoCpus()
    {
        $cpus = array(
                      'CPU_LOCAL' => array(
                                           'id' => 1,
                                           'host' => '127.0.0.1',
                                           'max' => 1,
                                           'number' => 1,
                                           'type' => CpuService::TYPE_LINUX,
                                           'user' => 'transco1',
                                           'password' => 'PUMUKIT',
                                           'description' => 'Pumukit transcoder'
                                           ),
                      'CPU_REMOTE' => array(
                                            'id' => 2,
                                            'host' => '192.168.5.123',
                                            'max' => 2,
                                            'number' => 1,
                                            'type' => CpuService::TYPE_LINUX,
                                            'user' => 'transco2',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder'
                                            )
                      );
        
        return $cpus;
    }

    private function getDemoProfiles()
    {
        $profiles = array(
                          'MASTER_COPY' => array(
                                                 'id' => 1,
                                                 'name' => 'master_copy',
                                                 'rank' => 1,
                                                 'display' => false,
                                                 'wizard' => true,
                                                 'master' => true,
                                                 'format' => '???',
                                                 'codec' => '??',
                                                 'mime_type' => '??',
                                                 'extension' => '???',
                                                 'resolution_hor' => 0,
                                                 'resolution_ver' => 0,
                                                 'bitrate' => '??',
                                                 'framerate' => 0,
                                                 'channels' => 1,
                                                 'audio' => false,
                                                 'bat' => 'cp "{{input}}" "{{output}}"',
                                                 'file_cfg' => '??',
                                                 'streamserver' => array(
                                                                         'streamserver_type' => ProfileService::STREAMSERVER_STORE,
                                                                         'ip' => '127.0.0.1',
                                                                         'name' => 'Localmaster',
                                                                         'description' => 'Local masters server',
                                                                         'dir_out' => '/mnt/nas/storage/masters',
                                                                         'url_out' => ''
                                                                         ),
                                                 'app' => 'cp',
                                                 'rel_duration_size' => 1,
                                                 'rel_duration_trans' => 1,
                                                 'prescript' => '?????'
                                                 ),
                          'MASTER_VIDEO_H264' => array(
                                                       'id' => 2,
                                                       'name' => 'master_video_h264',
                                                       'rank' => 2,
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
                                                       'framerate' => 25,
                                                       'channels' => 1,
                                                       'audio' => false,
                                                       'bat' => 'BitRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_format -print_format default=nk=1:nw=1 | sed -n 9p)
                                                                     [[ "$(( BitRate ))" -gt 6000000 ]] && : $(( BitRate = 6000000 ))

                                                                     FrameRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v -print_format default=nk=1:nw=1 | sed -n 18p)

                                                                     BufSize=$(( BitRate*20/FrameRate ))

                                                                     AudioSampleRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     AudioBitRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 22p)

                                                                     width=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 9p)

                                                                     [[ "$(( width % 2 ))" -ne 0 ]] && : $(( width += 1 ))

                                                                     height=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     [[ "$(( height % 2 ))" -ne 0 ]] && : $(( height += 1 ))

                                                                     /usr/local/bin/ffmpeg -y -i "{{input}}" -acodec libfdk_aac -b:a $AudioBitRate -ac 2 -ar $AudioSampleRate -vcodec libx264 -r 25 -preset slow -crf 15 -maxrate $BitRate -bufsize $BufSize -s $width"x"$height -threads 0 "{{output}}"',
                                                       'file_cfg' => '',
                                                       'streamserver' => array(
                                                                               'streamserver_type' => ProfileService::STREAMSERVER_STORE,
                                                                               'ip' => '192.168.5.125',
                                                                               'name' => 'Download',
                                                                               'description' => 'Download server',
                                                                               'dir_out' => '/mnt/nas/storage/downloads',
                                                                               'url_out' => 'http://localhost:8000/downloads/'
                                                                               ),
                                                       'app' => 'ffmpeg',
                                                       'rel_duration_size' => 1,
                                                       'rel_duration_trans' => 1,
                                                       'prescript' => '?????'
                                                       )
                          );

        return $profiles;
    }
}
