<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use Monolog\Logger;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobExecutor;
use Pumukit\EncoderBundle\Services\JobUpdater;
use Pumukit\EncoderBundle\Services\JobValidator;
use Pumukit\EncoderBundle\Services\MultimediaObjectPropertyJobService;
use Pumukit\EncoderBundle\Services\ProfileValidator;

/**
 * @internal
 *
 * @coversNothing
 */
final class JobUpdaterTest extends PumukitTestCase
{
    private $logger;
    private $profileValidator;
    private $multimediaObjectPropertyJobService;

    private $jobValidator;
    private $jobExecutor;

    private $jobUpdater;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger('test');
        $this->profileValidator = self::$kernel->getContainer()->get(ProfileValidator::class);
        $this->multimediaObjectPropertyJobService = self::$kernel->getContainer()->get(MultimediaObjectPropertyJobService::class);
        $this->jobValidator = self::$kernel->getContainer()->get(JobValidator::class);
        $this->jobExecutor = self::$kernel->getContainer()->get(JobExecutor::class);

        $this->jobUpdater = new JobUpdater(
            $this->dm,
            $this->logger,
            $this->profileValidator,
            $this->jobValidator,
            $this->jobExecutor,
            $this->multimediaObjectPropertyJobService
        );
    }

    public function testPausedJob(): void
    {
        $job = new Job();
        $job->setStatus(Job::STATUS_WAITING);
        $this->dm->persist($job);
        $this->dm->flush();

        $this->jobUpdater->pauseJob($job);

        $this->assertEquals(Job::STATUS_PAUSED, $job->getStatus());
    }

    public function testResumeJob(): void
    {
        $job = $this->createJobByStatus(Job::STATUS_PAUSED);
        $this->jobUpdater->resumeJob($job);

        $this->assertEquals(Job::STATUS_WAITING, $job->getStatus());
    }

    public function testErrorJob(): void
    {
        $job = $this->createJobByStatus(Job::STATUS_WAITING);
        $this->jobUpdater->errorJob($job);

        $this->assertEquals(Job::STATUS_ERROR, $job->getStatus());
    }

    public function testUpdatePriority(): void
    {
        $job = $this->createJobByStatus(Job::STATUS_WAITING);
        $this->jobUpdater->updateJobPriority($job, 2);

        $this->assertEquals(2, $job->getPriority());
    }

    public function testCancelJob(): void
    {
        $job = $this->createJobByStatus(Job::STATUS_WAITING);
        $this->expectException(\Exception::class);
        $this->jobUpdater->cancelJob($job);

        $job = $this->createJobByStatus(Job::STATUS_PAUSED);
        $this->expectException(\Exception::class);
        $this->jobUpdater->cancelJob($job);

        $job = $this->createJobByStatus(Job::STATUS_ERROR);
        $this->jobUpdater->cancelJob($job);
        $this->assertNull($this->dm->find(Job::class, $job->getId()));
    }

    private function createJobByStatus(int $status): Job
    {
        $job = new Job();
        $job->setStatus($status);
        $job->setPriority(1);
        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }
}
