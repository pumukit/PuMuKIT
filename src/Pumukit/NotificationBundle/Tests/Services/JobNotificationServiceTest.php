<?php

declare(strict_types=1);

namespace Pumukit\NotificationBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\NotificationBundle\Services\JobNotificationService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 */
#[CoversNothing]
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

        $this->i18nService = new i18nService(['en', 'es'], 'en');

        $this->factoryService = self::$kernel->getContainer()->get(FactoryService::class);
        $this->repo = $this->dm->getRepository(Job::class);

        $this->jobNotificationService = $this->containerHelper->get(JobNotificationService::class);
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
        $this->addOwner($multimediaObject, Permission::ROLE_SEND_NOTIFICATION_COMPLETE);
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
        static::assertEmailCount(1);
        static::assertEmailAddressContains(static::getMailerMessage(), 'To', 'owner@pumukit.local');
    }

    public function testOnJobError()
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($this->factoryService->createSeries());
        $this->addOwner($multimediaObject, Permission::ROLE_SEND_NOTIFICATION_ERRORS);
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
        static::assertEmailCount(1);
        static::assertEmailAddressContains(static::getMailerMessage(), 'To', 'owner@pumukit.local');
    }

    private function addOwner(MultimediaObject $multimediaObject, string $permission): void
    {
        $email = 'owner@pumukit.local';
        $person = new Person();
        $person->setName('Owner');
        $person->setEmail($email);
        $user = new User();
        $user->setUsername('owner');
        $user->setEmail($email);
        $user->setPassword('unused');
        $user->setRoles([$permission]);
        $user->setPerson($person);
        $person->setUser($user);
        $role = new Role();
        $role->setCod(JobNotificationService::PERSONAL_SCOPE_ROLE_CODE);
        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->persist($role);
        $multimediaObject->addPersonWithRole($person, $role);
        $this->dm->flush();
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
