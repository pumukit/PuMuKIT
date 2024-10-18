<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use Monolog\Logger;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class JobRemoverTest extends PumukitTestCase
{
    private $logger;
    private $eventDispatcher;
    private $trackService;
    private $projectDir;
    private $jobRemover;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger('test');
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->trackService = self::$kernel->getContainer()->get(TrackService::class);
        $this->projectDir = self::$kernel->getContainer()->getParameter('kernel.project_dir');

        $this->jobRemover = new JobRemover(
            $this->dm,
            $this->logger,
            $this->eventDispatcher,
            $this->trackService,
            $this->projectDir.'/test/tmp',
            $this->projectDir.'/inbox',
            true
        );
    }

    public function testDeleteTempFilesFromJob(): void
    {
        $job = $this->createJobExecutingStatus();

        $this->expectExceptionMessage('Cannot delete temp files from not finished jobs.');
        $this->jobRemover->deleteTempFilesFromJob($job);

        $job = $this->createJobFinishedStatus();
        $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.mp4', 'pumukit.mp4');
        $this->jobRemover->deleteTempFilesFromJob($job);

        $this->assertFalse(file_exists($job->getPathIni()));
    }

    public function testDelete()
    {
        $job = $this->createJobFinishedStatus();
        $this->jobRemover->delete($job);
        $this->assertNull($this->dm->getRepository(Job::class)->findOneBy(['_id' => $job->getId()]));

        $job = $this->createJobExecutingStatus();

        $this->expectException(\Exception::class);
        $this->jobRemover->delete($job);
    }

    private function createJob(): Job
    {
        $job = new Job();
        $job->setStatus(Job::STATUS_WAITING);
        $job->setPathIni($this->projectDir.'/tests/tmp/pumukit.mp4');
        $job->setPathEnd($this->projectDir.'/tests/files/pumukit.mp4');

        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }

    private function createJobExecutingStatus(): Job
    {
        $job = $this->createJob();
        $job->setStatus(Job::STATUS_EXECUTING);

        $this->dm->flush();

        return $job;
    }

    private function createJobFinishedStatus(): Job
    {
        $job = $this->createJob();
        $job->setStatus(Job::STATUS_FINISHED);

        $this->dm->flush();

        return $job;
    }

    private function createJobFinishedFromInbox(): Job
    {
        $job = $this->createJobFinishedStatus();
        $job->setPathIni($this->projectDir.'/inbox/pumukit.mp4');
        $this->dm->flush();

        return $job;
    }

    private function copyFileToUse(string $origin, string $fileName): string
    {
        $tmpPath = $this->projectDir.'/tests/tmp/';
        copy($origin, $this->projectDir.'/tests/tmp/'.$fileName);

        return $tmpPath.$fileName;
    }
}
