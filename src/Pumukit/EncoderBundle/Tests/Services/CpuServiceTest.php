<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;

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
        $cpus = $this->getDemoCpus();

        $this->assertEquals($cpus['CPU_LOCAL'], $this->cpuService->getFreeCpu());

        $job = new Job();
        $job->setCpuId(1);
        $job->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job);
        $this->dm->flush();

        $this->assertEquals($cpus['CPU_REMOTE'], $this->cpuService->getFreeCpu());

        $job2 = new Job();
        $job2->setCpuId(2);
        $job2->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job2);
        $this->dm->flush();

        $this->assertEquals($cpus['CPU_REMOTE'], $this->cpuService->getFreeCpu());

        $job3 = new Job();
        $job3->setCpuId(2);
        $job3->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job3);
        $this->dm->flush();

        $this->assertNull($this->cpuService->getFreeCpu());
    }

    public function testGetCpus()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals(2, count($this->cpuService->getCpus()));
        $this->assertEquals(count($cpus), count($this->cpuService->getCpus()));
    }

    public function testGetCpuByName()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals($cpus['CPU_LOCAL'], $this->cpuService->getCpuByName('cpu_local'));
        $this->assertEquals($cpus['CPU_REMOTE'], $this->cpuService->getCpuByName('cpu_remote'));
        $this->assertNull($this->cpuService->getCpuByName('cpu_lo'));
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
}