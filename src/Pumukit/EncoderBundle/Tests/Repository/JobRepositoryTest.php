<?php

namespace Pumukit\EncoderBundle\Tests\Repository;

use Pumukit\EncoderBundle\Document\Job;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class JobRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Job::class);

        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $name = 'video1';

        $job = $this->newJob($mm_id, $name);

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testFindWithStatus()
    {
        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $name = 'video1';
        $pausedJob = $this->newJob($mm_id, $name);
        $pausedJob->setStatus(Job::STATUS_PAUSED);

        $mm_id = '54ad3f5e6e4cd68a278b4574';
        $name = 'video2';
        $waitingJob = $this->newJob($mm_id, $name);
        $waitingJob->setStatus(Job::STATUS_WAITING);

        $mm_id = '54ad3f5e6e4cd68a278b4575';
        $name = 'video3';
        $executingJob = $this->newJob($mm_id, $name);
        $executingJob->setStatus(Job::STATUS_EXECUTING);

        $mm_id = '54ad3f5e6e4cd68a278b4576';
        $name = 'video4';
        $executingJob2 = $this->newJob($mm_id, $name);
        $executingJob2->setStatus(Job::STATUS_EXECUTING);

        $mm_id = '54ad3f5e6e4cd68a278b4577';
        $name = 'video5';
        $errorJob = $this->newJob($mm_id, $name);
        $errorJob->setStatus(Job::STATUS_ERROR);

        $mm_id = '54ad3f5e6e4cd68a278b4578';
        $name = 'video6';
        $finishedJob = $this->newJob($mm_id, $name);
        $finishedJob->setStatus(Job::STATUS_FINISHED);

        $this->dm->persist($pausedJob);
        $this->dm->persist($waitingJob);
        $this->dm->persist($executingJob);
        $this->dm->persist($executingJob2);
        $this->dm->persist($errorJob);
        $this->dm->persist($finishedJob);
        $this->dm->flush();

        $pausedJobs = $this->repo->findWithStatus([Job::STATUS_PAUSED])->toArray();
        $waitingJobs = $this->repo->findWithStatus([Job::STATUS_WAITING])->toArray();
        $executingJobs = $this->repo->findWithStatus([Job::STATUS_EXECUTING])->toArray();
        $finishedJobs = $this->repo->findWithStatus([Job::STATUS_FINISHED])->toArray();
        $errorJobs = $this->repo->findWithStatus([Job::STATUS_ERROR])->toArray();
        $pausedAndWaitingJobs = $this->repo->findWithStatus([Job::STATUS_PAUSED, Job::STATUS_WAITING])->toArray();
        $pausedFinishedAndErrorJobs = $this->repo->findWithStatus([Job::STATUS_PAUSED, Job::STATUS_FINISHED, Job::STATUS_ERROR])->toArray();

        $this->assertCount(1, $pausedJobs);
        $this->assertCount(1, $waitingJobs);
        $this->assertCount(2, $executingJobs);
        $this->assertCount(1, $finishedJobs);
        $this->assertCount(1, $errorJobs);
        $this->assertCount(2, $pausedAndWaitingJobs);
        $this->assertCount(3, $pausedFinishedAndErrorJobs);

        $this->assertTrue(in_array($pausedJob, $pausedJobs));
        $this->assertFalse(in_array($waitingJob, $pausedJobs));
        $this->assertFalse(in_array($executingJob, $pausedJobs));
        $this->assertFalse(in_array($executingJob2, $pausedJobs));
        $this->assertFalse(in_array($finishedJob, $pausedJobs));
        $this->assertFalse(in_array($errorJob, $pausedJobs));

        $this->assertFalse(in_array($pausedJob, $waitingJobs));
        $this->assertTrue(in_array($waitingJob, $waitingJobs));
        $this->assertFalse(in_array($executingJob, $waitingJobs));
        $this->assertFalse(in_array($executingJob2, $waitingJobs));
        $this->assertFalse(in_array($finishedJob, $waitingJobs));
        $this->assertFalse(in_array($errorJob, $waitingJobs));

        $this->assertFalse(in_array($pausedJob, $executingJobs));
        $this->assertFalse(in_array($waitingJob, $executingJobs));
        $this->assertTrue(in_array($executingJob, $executingJobs));
        $this->assertTrue(in_array($executingJob2, $executingJobs));
        $this->assertFalse(in_array($finishedJob, $executingJobs));
        $this->assertFalse(in_array($errorJob, $executingJobs));

        $this->assertFalse(in_array($pausedJob, $finishedJobs));
        $this->assertFalse(in_array($waitingJob, $finishedJobs));
        $this->assertFalse(in_array($executingJob, $finishedJobs));
        $this->assertFalse(in_array($executingJob2, $finishedJobs));
        $this->assertTrue(in_array($finishedJob, $finishedJobs));
        $this->assertFalse(in_array($errorJob, $finishedJobs));

        $this->assertFalse(in_array($pausedJob, $errorJobs));
        $this->assertFalse(in_array($waitingJob, $errorJobs));
        $this->assertFalse(in_array($executingJob, $errorJobs));
        $this->assertFalse(in_array($executingJob2, $errorJobs));
        $this->assertFalse(in_array($finishedJob, $errorJobs));
        $this->assertTrue(in_array($errorJob, $errorJobs));
    }

    public function testFindHigherPriorityWithStatus()
    {
        $mm_id = '54ad3f5e6e4cd68a278b4578';
        $name = 'video0';
        $job0 = $this->newJob($mm_id, $name);
        $job0->setTimeini(new \DateTime('15-12-2015 9:00:00'));
        $job0->setPriority(3);
        $job0->setStatus(Job::STATUS_PAUSED);

        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $name = 'video1';
        $job1 = $this->newJob($mm_id, $name);
        $job1->setTimeini(new \DateTime('15-12-2015 9:00:01'));
        $job1->setPriority(2);

        $mm_id = '54ad3f5e6e4cd68a278b4574';
        $name = 'video2';
        $job2 = $this->newJob($mm_id, $name);
        $job2->setTimeini(new \DateTime('15-12-2015 9:00:02'));
        $job2->setPriority(1);

        $mm_id = '54ad3f5e6e4cd68a278b4575';
        $name = 'video3';
        $job3 = $this->newJob($mm_id, $name);
        $job3->setTimeini(new \DateTime('15-12-2015 9:00:03'));
        $job3->setPriority(3);

        $mm_id = '54ad3f5e6e4cd68a278b4576';
        $name = 'video4';
        $job4 = $this->newJob($mm_id, $name);
        $job4->setTimeini(new \DateTime('15-12-2015 9:00:04'));
        $job4->setPriority(2);

        $mm_id = '54ad3f5e6e4cd68a278b4577';
        $name = 'video5';
        $job5 = $this->newJob($mm_id, $name);
        $job5->setTimeini(new \DateTime('15-12-2015 9:00:05'));
        $job5->setPriority(1);

        $mm_id = '54ad3f5e6e4cd68a278b4578';
        $name = 'video6';
        $job6 = $this->newJob($mm_id, $name);
        $job6->setTimeini(new \DateTime('15-12-2015 9:00:06'));
        $job6->setPriority(2);

        $this->dm->persist($job0);
        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->persist($job4);
        $this->dm->persist($job5);
        $this->dm->persist($job6);
        $this->dm->flush();

        $this->assertEquals($job3, $this->repo->findHigherPriorityWithStatus([Job::STATUS_WAITING]));

        $mm_id = '54ad3f5e6e4cd68a278b4578';
        $name = 'video7';
        $job7 = $this->newJob($mm_id, $name);
        $job7->setTimeini(new \DateTime('15-12-2015 9:00:07'));
        $job7->setPriority(3);

        $this->dm->persist($job7);
        $this->dm->flush();

        $this->assertEquals($job3, $this->repo->findHigherPriorityWithStatus([Job::STATUS_WAITING]));
        $this->assertNotEquals($job7, $this->repo->findHigherPriorityWithStatus([Job::STATUS_WAITING]));

        $this->assertEquals($job0, $this->repo->findHigherPriorityWithStatus([Job::STATUS_PAUSED]));
    }

    public function testFindNotFinishedByMultimediaObjectId()
    {
        $mm_id1 = '54ad3f5e6e4cd68a278b4573';
        $mm_id2 = '54ad3f5e6e4cd68a278b4574';

        $job1 = new Job();
        $job2 = new Job();
        $job3 = new Job();

        $job1->setMmId($mm_id1);
        $job2->setMmId($mm_id2);
        $job3->setMmId($mm_id1);

        $job1->setStatus(Job::STATUS_FINISHED);
        $job2->setStatus(Job::STATUS_WAITING);
        $job3->setStatus(Job::STATUS_EXECUTING);

        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findNotFinishedByMultimediaObjectId($mm_id1)));
        $this->assertEquals(1, count($this->repo->findNotFinishedByMultimediaObjectId($mm_id2)));
    }

    public function testFindByStatusAndMultimediaObjectId()
    {
        $mm_id1 = '54ad3f5e6e4cd68a278b4573';
        $mm_id2 = '54ad3f5e6e4cd68a278b4574';

        $job1 = new Job();
        $job2 = new Job();
        $job3 = new Job();
        $job4 = new Job();

        $job1->setMmId($mm_id1);
        $job2->setMmId($mm_id2);
        $job3->setMmId($mm_id1);
        $job4->setMmId($mm_id1);

        $job1->setStatus(Job::STATUS_FINISHED);
        $job2->setStatus(Job::STATUS_EXECUTING);
        $job3->setStatus(Job::STATUS_WAITING);
        $job4->setStatus(Job::STATUS_WAITING);

        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->persist($job4);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findByStatusAndMultimediaObjectId(Job::STATUS_FINISHED, $mm_id1)));
        $this->assertEquals(2, count($this->repo->findByStatusAndMultimediaObjectId(Job::STATUS_WAITING, $mm_id1)));
        $this->assertEquals(0, count($this->repo->findByStatusAndMultimediaObjectId(Job::STATUS_WAITING, $mm_id2)));
        $this->assertEquals(1, count($this->repo->findByStatusAndMultimediaObjectId(Job::STATUS_EXECUTING, $mm_id2)));
    }

    public function testFindByMultimediaObjectId()
    {
        $mm_id1 = '54ad3f5e6e4cd68a278b4573';
        $mm_id2 = '54ad3f5e6e4cd68a278b4574';

        $job1 = new Job();
        $job2 = new Job();
        $job3 = new Job();
        $job4 = new Job();

        $job1->setMmId($mm_id1);
        $job2->setMmId($mm_id2);
        $job3->setMmId($mm_id1);
        $job4->setMmId($mm_id1);

        $job1->setStatus(Job::STATUS_FINISHED);
        $job2->setStatus(Job::STATUS_EXECUTING);
        $job3->setStatus(Job::STATUS_WAITING);
        $job4->setStatus(Job::STATUS_WAITING);

        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->persist($job4);
        $this->dm->flush();

        $this->assertEquals(3, count($this->repo->findByMultimediaObjectId($mm_id1)));
        $this->assertEquals(1, count($this->repo->findByMultimediaObjectId($mm_id2)));
    }

    public function testFindByMultimediaObjectIdAndProfile()
    {
        $mm_id1 = '54ad3f5e6e4cd68a278b4573';
        $mm_id2 = '54ad3f5e6e4cd68a278b4574';

        $job1 = new Job();
        $job2 = new Job();
        $job3 = new Job();
        $job4 = new Job();

        $job1->setMmId($mm_id1);
        $job2->setMmId($mm_id2);
        $job3->setMmId($mm_id1);
        $job4->setMmId($mm_id1);

        $job1->setStatus(Job::STATUS_FINISHED);
        $job2->setStatus(Job::STATUS_EXECUTING);
        $job3->setStatus(Job::STATUS_WAITING);
        $job4->setStatus(Job::STATUS_WAITING);

        $job1->setProfile('master');
        $job2->setProfile('video_h264');
        $job3->setProfile('master');
        $job4->setProfile('video_h264');

        $this->dm->persist($job1);
        $this->dm->persist($job2);
        $this->dm->persist($job3);
        $this->dm->persist($job4);
        $this->dm->flush();

        $masterProfile = 'master';
        $videoH264Profile = 'video_h264';

        $this->assertEquals(2, count($this->repo->findByMultimediaObjectIdAndProfile($mm_id1, $masterProfile)));
        $this->assertEquals(1, count($this->repo->findByMultimediaObjectIdAndProfile($mm_id1, $videoH264Profile)));
        $this->assertEquals(0, count($this->repo->findByMultimediaObjectIdAndProfile($mm_id2, $masterProfile)));
        $this->assertEquals(1, count($this->repo->findByMultimediaObjectIdAndProfile($mm_id2, $videoH264Profile)));
    }

    private function newJob($mm_id, $name)
    {
        $job = new Job();

        $language_id = 'es';
        $profile = 1;
        $cpu = 'local';
        $url = 'video/'.$mm_id.'/'.$name.'.avi';
        $status = Job::STATUS_WAITING;
        $priority = 1;
        $timeini = new \DateTime('now');
        $timestart = new \DateTime('now');
        $timeend = new \DateTime('now');
        $pid = 3;
        $path_ini = 'path/ini';
        $path_end = 'path/end';
        $ext_ini = 'ext/ini';
        $ext_end = 'ext/end';
        $duration = 40;
        $size = '12000';
        $email = 'test@mail.com';
        $locale = 'en';

        $job->setLocale('en');
        $job->setMmId($mm_id);
        $job->setLanguageId($language_id);
        $job->setProfile($profile);
        $job->setCpu($cpu);
        $job->setUrl($url);
        $job->setStatus($status);
        $job->setPriority($priority);
        $job->setName($name);
        $job->setTimeini($timeini);
        $job->setTimestart($timestart);
        $job->setTimeend($timeend);
        $job->setPid($pid);
        $job->setPathIni($path_ini);
        $job->setPathEnd($path_end);
        $job->setExtIni($ext_ini);
        $job->setExtEnd($ext_end);
        $job->setDuration($duration);
        $job->setSize($size);
        $job->setEmail($email);

        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }
}
