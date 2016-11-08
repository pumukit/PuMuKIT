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

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');

        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->flush();

        $this->cpuService = new CpuService($this->getDemoCpus(), $this->dm);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->cpuService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetFreeCpu()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals('CPU_REMOTE', $this->cpuService->getFreeCpu());

        $job = new Job();
        $job->setCpu('CPU_REMOTE');
        $job->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job);
        $this->dm->flush();

        $this->assertEquals('CPU_LOCAL', $this->cpuService->getFreeCpu());

        $job2 = new Job();
        $job2->setCpu('CPU_LOCAL');
        $job2->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job2);
        $this->dm->flush();

        $this->assertEquals('CPU_CLOUD', $this->cpuService->getFreeCpu());

        $job3 = new Job();
        $job3->setCpu('CPU_CLOUD');
        $job3->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job3);
        $this->dm->flush();

        $this->assertEquals('CPU_REMOTE', $this->cpuService->getFreeCpu());

        $job4 = new Job();
        $job4->setCpu('CPU_REMOTE');
        $job4->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job4);
        $this->dm->flush();

        $this->assertNull($this->cpuService->getFreeCpu());
    }

    public function testGetCpus()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals(3, count($this->cpuService->getCpus()));
        $this->assertEquals(count($cpus), count($this->cpuService->getCpus()));
    }

    public function testGetCpuByName()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals($cpus['CPU_LOCAL'], $this->cpuService->getCpuByName('CPU_LOCAL'));
        $this->assertEquals($cpus['CPU_REMOTE'], $this->cpuService->getCpuByName('CPU_REMOTE'));
        $this->assertEquals($cpus['CPU_CLOUD'], $this->cpuService->getCpuByName('CPU_CLOUD'));
        $this->assertNull($this->cpuService->getCpuByName('CPU_local')); //Case sensitive
        $this->assertNull($this->cpuService->getCpuByName('CPU_LO'));
    }

    private function getDemoCpus()
    {
        $cpus = array(
                      'CPU_LOCAL' => array(
                                           'host' => '127.0.0.1',
                                           'max' => 1,
                                           'number' => 1,
                                           'type' => CpuService::TYPE_LINUX,
                                           'user' => 'transco1',
                                           'password' => 'PUMUKIT',
                                           'description' => 'Pumukit transcoder',
                                           ),
                      'CPU_REMOTE' => array(
                                            'host' => '192.168.5.123',
                                            'max' => 2,
                                            'number' => 1,
                                            'type' => CpuService::TYPE_LINUX,
                                            'user' => 'transco2',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder',
                                            ),
                      'CPU_CLOUD' => array(
                                            'host' => '192.168.5.124',
                                            'max' => 1,
                                            'number' => 1,
                                            'type' => CpuService::TYPE_LINUX,
                                            'user' => 'transco2',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder',
                                            ),
                      );

        return $cpus;
    }
}
