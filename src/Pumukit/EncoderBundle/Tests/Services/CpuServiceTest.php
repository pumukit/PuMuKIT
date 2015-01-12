<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;

class CpuServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $cpuService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitEncoderBundle:Job');
        $this->cpuService = $kernel->getContainer()
          ->get('pumukitencoder.cpu');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->flush();
    }

    public function testGetFreeCpu()
    {
        $freeCpus = $this->cpuService->getFreeCpu();
        $this->assertEquals(2, count($freeCpus));

        $job = new Job();
        $job->setCpuId(1);
        $job->setStatusId(Job::STATUS_EXECUTING);
        $this->dm->persist($job);
        $this->dm->flush();

        $freeCpus2 = $this->cpuService->getFreeCpu();
        $this->assertEquals(1, count($freeCpus2));

        $job2 = new Job();
        $job2->setCpuId(2);
        $job2->setStatusId(Job::STATUS_EXECUTING);
        $this->dm->persist($job2);
        $this->dm->flush();

        $freeCpus3 = $this->cpuService->getFreeCpu();
        $this->assertEquals(1, count($freeCpus3));

        $job3 = new Job();
        $job3->setCpuId(2);
        $job3->setStatusId(Job::STATUS_EXECUTING);
        $this->dm->persist($job3);
        $this->dm->flush();

        $freeCpus4 = $this->cpuService->getFreeCpu();
        $this->assertEquals(0, count($freeCpus4));
    }
}