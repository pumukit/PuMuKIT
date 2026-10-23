<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class TrackListTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->dm->getDocumentCollection(Job::class)->deleteMany([]);

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('tracklist');
        $user->setEmail('tracklist@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $factory = $container->get(FactoryService::class);
        $this->multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('jobStatuses')]
    public function testTheListRefreshesWhileJobsArePending(?int $status, bool $refreshes): void
    {
        if (null !== $status) {
            $job = new Job();
            $job->setMmId($this->multimediaObject->getId());
            $job->setProfile('master_copy');
            $job->setStatus($status);
            $job->setPathIni('/tmp/in.mp4');
            $job->setTimeini(new \DateTime());
            $this->dm->persist($job);
            $this->dm->flush();
        }

        $this->client->request('GET', static::getContainer()->get('router')->generate('pumukitnewadmin_track_list', ['id' => $this->multimediaObject->getId()]));

        $this->assertResponseIsSuccessful();
        $refreshes
            ? $this->assertSelectorExists('table[data-admin--track-list-poll-value]')
            : $this->assertSelectorNotExists('table[data-admin--track-list-poll-value]');
    }

    public static function jobStatuses(): array
    {
        return [
            'no jobs' => [null, false],
            'waiting job' => [Job::STATUS_WAITING, true],
            'executing job' => [Job::STATUS_EXECUTING, true],
            'failed job' => [Job::STATUS_ERROR, false],
        ];
    }
}
