<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\ApiResource;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
final class DashboardApiTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $admin;
    private User $teacher;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);

        $this->admin = $this->user('dashboard-admin', ['ROLE_SUPER_ADMIN']);
        $this->teacher = $this->user('dashboard-teacher', [PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_DASHBOARD']);
        $person = new Person();
        $person->setName('Teacher');
        $person->setUser($this->teacher);
        $this->teacher->setPerson($person);
        $this->dm->persist($person);

        $factory = self::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries();
        $own = $this->multimediaObject($factory, $series, MultimediaObject::STATUS_PUBLISHED, 600, '-1 month');
        $own->addPersonWithRole($person, $role);
        $this->multimediaObject($factory, $series, MultimediaObject::STATUS_HIDDEN, 1200, 'now');
        $this->multimediaObject($factory, $factory->createSeries(), MultimediaObject::STATUS_BLOCKED, 1800, '-14 months');

        foreach ([Job::STATUS_WAITING, Job::STATUS_EXECUTING, Job::STATUS_ERROR] as $status) {
            $job = new Job();
            $job->setStatus($status);
            $job->setTimeend(new \DateTime('-1 hour'));
            $this->dm->persist($job);
        }
        $this->dm->flush();
    }

    public function testAGlobalUserSeesTheWholeCatalogue(): void
    {
        $summary = $this->get($this->admin, 'summary');
        $this->assertSame(2, $summary['series']);
        $this->assertSame(3, $summary['multimediaObjects']);
        $this->assertSame(3600, $summary['duration']);

        $status = array_column($this->get($this->admin, 'status')['items'], 'count', 'key');
        $this->assertSame(['published' => 1, 'hidden' => 1, 'blocked' => 1, 'withoutMedia' => 3], $status);

        $activity = $this->get($this->admin, 'activity')['months'];
        $this->assertCount(12, $activity);
        $this->assertSame(1, end($activity)['count']);
        $this->assertSame(2, array_sum(array_column($activity, 'count')));

        $jobs = array_column($this->get($this->admin, 'jobs')['items'], 'count', 'key');
        $this->assertSame(['waiting' => 1, 'executing' => 1, 'paused' => 0, 'finished' => 0, 'error' => 1], $jobs);

        $this->assertArrayHasKey('items', $this->get($this->admin, 'storage'));
    }

    public function testATeacherOnlySeesTheirOwnMultimediaObjects(): void
    {
        $summary = $this->get($this->teacher, 'summary');
        $this->assertSame(1, $summary['series']);
        $this->assertSame(1, $summary['multimediaObjects']);
        $this->assertSame(600, $summary['duration']);

        $status = array_column($this->get($this->teacher, 'status')['items'], 'count', 'key');
        $this->assertSame(['published' => 1, 'hidden' => 0, 'blocked' => 0, 'withoutMedia' => 1], $status);

        $this->request($this->teacher, 'jobs');
        $this->assertResponseStatusCodeSame(403);

        $this->request($this->teacher, 'storage');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheDashboardPageShowsTheCardsOfTheUserPermissions(): void
    {
        $this->client->loginUser($this->admin, 'pumukit');
        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseIsSuccessful();
        $urls = json_decode($this->client->getCrawler()->filter('[data-controller="admin--dashboard"]')->attr('data-admin--dashboard-urls-value'), true);
        $this->assertSame(['summary', 'activity', 'status', 'views', 'jobs', 'storage'], array_keys($urls));

        $this->client->loginUser($this->teacher, 'pumukit');
        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseIsSuccessful();
        $urls = json_decode($this->client->getCrawler()->filter('[data-controller="admin--dashboard"]')->attr('data-admin--dashboard-urls-value'), true);
        $this->assertSame(['summary', 'activity', 'status'], array_keys($urls));
    }

    private function get(User $user, string $report): array
    {
        $this->request($user, $report);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function request(User $user, string $report): void
    {
        $this->client->loginUser($user, 'pumukit');
        $this->client->request('GET', '/api/v1/dashboard/'.$report, [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    private function user(string $username, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles($roles);
        $this->dm->persist($user);

        return $user;
    }

    private function multimediaObject(FactoryService $factory, $series, int $status, int $duration, string $created): MultimediaObject
    {
        $multimediaObject = $factory->createMultimediaObject($series);
        $multimediaObject->setStatus($status);
        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        $multimediaObject->setDuration($duration);
        $multimediaObject->setProperty('created', (new \DateTimeImmutable($created))->format('c'));

        return $multimediaObject;
    }
}
