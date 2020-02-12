<?php

namespace Pumukit\NotificationBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class JobNotificationServiceTest extends PumukitTestCase
{
    private $repo;
    private $containerHelper;
    private $jobNotificationService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        self::bootKernel($options);
        parent::setUp();
        $this->containerHelper = self::$kernel->getContainer();

        if (!array_key_exists('PumukitNotificationBundle', $this->containerHelper->getParameter('kernel.bundles')) ||
            false === $this->containerHelper->getParameter('pumukit_notification.enable')) {
            static::markTestSkipped('NotificationBundle is not installed');
        }

        $this->repo = $this->dm->getRepository(Job::class);

        $this->jobNotificationService = $this->containerHelper->get('pumukit_notification.listener');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->containerHelper = null;

        $this->repo = null;
        $this->jobNotificationService = null;
        gc_collect_cycles();
    }

    public function testOnJobSuccess()
    {
        $multimediaObject = $this->createNewMultimediaObjectWithTrack();
        $track = $multimediaObject->getTracks()[0];

        $job = $this->createNewJob(Job::STATUS_WAITING, $multimediaObject);

        $job->setStatus(Job::STATUS_FINISHED);
        $this->dm->persist($job);
        $this->dm->flush();

        $event = new JobEvent($job, $track, $multimediaObject);
        $output = $this->jobNotificationService->onJobSuccess($event);

        $this->assertEquals(1, $output);
        $this->assertCount(1, $this->repo->findAll());
    }

    public function testOnJobError()
    {
        $multimediaObject = $this->createNewMultimediaObjectWithTrack();
        $track = $multimediaObject->getTracks()[0];

        $job = $this->createNewJob(Job::STATUS_WAITING, $multimediaObject);

        $job->setStatus(Job::STATUS_ERROR);
        $this->dm->persist($job);
        $this->dm->flush();

        $event = new JobEvent($job, $track, $multimediaObject);
        $output = $this->jobNotificationService->onJobError($event);

        $this->assertEquals(1, $output);
        $this->assertCount(1, $this->repo->findAll());
    }

    private function createNewJob($status, $multimediaObject)
    {
        $job = new Job();
        if (null !== $status) {
            $job->setStatus($status);
        }
        $job->setMmId($multimediaObject->getId());
        $job->setTimeini(new \DateTime('now'));
        $job->setTimestart(new \DateTime('now'));
        $job->setDuration(60);
        $job->setNewDuration(65);
        $job->setProfile('master_copy');
        $job->setCpu('cpu_local');
        $job->setOutput('OK');
        $job->setEmail('test@test.com');
        $job->setPathIni('pathini/to/track.mp4');
        $job->setPathEnd('pathend/to/track.mp4');
        $this->dm->persist($job);
        $this->dm->flush();

        return $job;
    }

    private function createNewMultimediaObjectWithTrack()
    {
        $track = new Track();
        $track->addTag('profile:master');
        $track->setPath('path/to/track.mp4');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('MultimediaObject test');
        $multimediaObject->addTrack($track);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
}
