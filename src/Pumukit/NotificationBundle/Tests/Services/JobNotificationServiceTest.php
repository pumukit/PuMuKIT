<?php

declare(strict_types=1);

namespace Pumukit\NotificationBundle\Tests\Services;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 *
 * @coversNothing
 */
class JobNotificationServiceTest extends PumukitTestCase
{
    private $repo;
    private $containerHelper;
    private $jobNotificationService;
    private $factoryService;
    private $i18nService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        self::bootKernel($options);
        parent::setUp();
        $this->containerHelper = self::$kernel->getContainer();

        if (!array_key_exists('PumukitNotificationBundle', $this->containerHelper->getParameter('kernel.bundles'))
            || false === $this->containerHelper->getParameter('pumukit_notification.enable')) {
            static::markTestSkipped('NotificationBundle is not installed');
        }

        $this->i18nService = new i18nService(['en', 'es'], 'en');

        $this->factoryService = self::$kernel->getContainer()->get(FactoryService::class);
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
        $multimediaObject = $this->factoryService->createMultimediaObject($this->factoryService->createSeries());
        $track = $this->generateTrackMedia();
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $job = $this->createNewJob(Job::STATUS_WAITING, $multimediaObject);

        $job->setStatus(Job::STATUS_FINISHED);
        $this->dm->persist($job);
        $this->dm->flush();

        $event = new JobEvent($job, $track, $multimediaObject);
        $output = $this->jobNotificationService->onJobSuccess($event);

        static::assertEquals(1, $output);
        static::assertCount(1, $this->repo->findAll());
    }

    public function testOnJobError()
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($this->factoryService->createSeries());
        $track = $this->generateTrackMedia();
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $job = $this->createNewJob(Job::STATUS_WAITING, $multimediaObject);

        $job->setStatus(Job::STATUS_ERROR);
        $this->dm->persist($job);
        $this->dm->flush();

        $event = new JobEvent($job, $track, $multimediaObject);
        $output = $this->jobNotificationService->onJobError($event);

        static::assertEquals(1, $output);
        static::assertCount(1, $this->repo->findAll());
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

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = StorageUrl::create('');
        $path = Path::create('public/storage');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"format":{"duration":"10.000000"}}');

        return Track::create(
            $originalName,
            $description,
            $language,
            $tags,
            false,
            true,
            $views,
            $storage,
            $mediaMetadata
        );
    }
}
