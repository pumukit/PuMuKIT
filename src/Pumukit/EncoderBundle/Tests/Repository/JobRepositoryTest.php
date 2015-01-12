<?php

namespace Pumukit\EncoderBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;

class JobRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

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

        $this->assertEquals(1, count($this->repo->findWithStatus(array(Job::STATUS_PAUSED))));
        $this->assertEquals(1, count($this->repo->findWithStatus(array(Job::STATUS_WAITING))));
        $this->assertEquals(2, count($this->repo->findWithStatus(array(Job::STATUS_EXECUTING))));
        $this->assertEquals(1, count($this->repo->findWithStatus(array(Job::STATUS_FINISHED))));
        $this->assertEquals(1, count($this->repo->findWithStatus(array(Job::STATUS_ERROR))));
        $this->assertEquals(2, count($this->repo->findWithStatus(array(Job::STATUS_PAUSED, Job::STATUS_WAITING))));
        $this->assertEquals(3, count($this->repo->findWithStatus(array(Job::STATUS_PAUSED, Job::STATUS_FINISHED, Job::STATUS_ERROR))));
    }

    private function newJob($mm_id, $name)
    {
        $job = new Job();

        $language_id = 'es';
        $profile = 1;
        $cpu_id = 2;
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
        $job->setCpuId($cpu_id);
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