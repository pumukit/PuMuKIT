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

    private function newJob($mm_id, $name)
    {
        $job = new Job();

        $language_id = 'es';
        $profile_id = 1;
        $cpu_id = 2;
        $url = 'video/'.$mm_id.'/'.$name.'.avi';
        $status_id = Job::STATUS_WAITING;
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
        $job->setProfileId($profile_id);
        $job->setCpuId($cpu_id);
        $job->setUrl($url);
        $job->setStatusId($status_id);
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