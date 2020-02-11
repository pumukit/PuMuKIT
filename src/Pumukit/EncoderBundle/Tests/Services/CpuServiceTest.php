<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;

/**
 * @internal
 * @coversNothing
 */
class CpuServiceTest extends PumukitTestCase
{
    private $repo;
    private $cpuService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Job::class);

        $this->cpuService = new CpuService($this->getDemoCpus(), $this->dm);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->cpuService = null;
        gc_collect_cycles();
    }

    public function testGetFreeCpu()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals('CPU_REMOTE', $this->cpuService->getFreeCpu('video_h264'));

        $job = new Job();
        $job->setCpu('CPU_REMOTE');
        $job->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job);
        $this->dm->flush();

        $this->assertEquals('CPU_LOCAL', $this->cpuService->getFreeCpu('video_h264'));

        $job2 = new Job();
        $job2->setCpu('CPU_LOCAL');
        $job2->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job2);
        $this->dm->flush();

        $this->assertEquals('CPU_CLOUD', $this->cpuService->getFreeCpu('video_h264'));

        $job3 = new Job();
        $job3->setCpu('CPU_CLOUD');
        $job3->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job3);
        $this->dm->flush();

        $this->assertEquals('CPU_REMOTE', $this->cpuService->getFreeCpu('video_h264'));

        $job4 = new Job();
        $job4->setCpu('CPU_REMOTE');
        $job4->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job4);
        $this->dm->flush();

        $this->assertNull($this->cpuService->getFreeCpu('video_h264'));
        $this->assertEquals('CPU_WEBM', $this->cpuService->getFreeCpu('master_webm'));
        $this->assertEquals('CPU_WEBM', $this->cpuService->getFreeCpu('video_webm'));
        $this->assertEquals('CPU_WEBM', $this->cpuService->getFreeCpu());
    }

    public function testGetCpus()
    {
        $cpus = $this->getDemoCpus();

        $this->assertEquals(4, count($this->cpuService->getCpus()));
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
        return [
            'CPU_WEBM' => [
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco4',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
                'profiles' => ['master_webm', 'video_webm'],
            ],
            'CPU_LOCAL' => [
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco1',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_REMOTE' => [
                'host' => '192.168.5.123',
                'max' => 2,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_CLOUD' => [
                'host' => '192.168.5.124',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
        ];
    }
}
