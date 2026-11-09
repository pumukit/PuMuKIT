<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\ApiResource;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
final class ApiScopeTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $admin;
    private User $publisher;
    private User $teacher;
    private MultimediaObject $own;
    private MultimediaObject $foreign;
    private Series $ownSeries;
    private Series $foreignSeries;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);

        $this->admin = $this->user('api-admin', ['ROLE_SUPER_ADMIN']);
        $this->publisher = $this->user('api-publisher', [PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_API']);
        $this->teacher = $this->user('api-teacher', [PermissionProfile::SCOPE_PERSONAL]);

        $person = new Person();
        $person->setName('Publisher');
        $person->setEmail('publisher@pumukit.local');
        $person->setUser($this->publisher);
        $this->publisher->setPerson($person);
        $this->dm->persist($person);

        $factory = self::getContainer()->get(FactoryService::class);
        $this->ownSeries = $factory->createSeries();
        $this->own = $factory->createMultimediaObject($this->ownSeries);
        $this->own->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->own->addPersonWithRole($person, $role);

        $this->foreignSeries = $factory->createSeries();
        $this->foreign = $factory->createMultimediaObject($this->foreignSeries);
        $this->foreign->setStatus(MultimediaObject::STATUS_HIDDEN);
        $this->dm->flush();
    }

    public function testAGlobalUserListsEveryMultimediaObjectButThePrototypes(): void
    {
        $ids = array_column($this->members($this->admin, '/api/v1/multimedia_objects'), 'id');
        $this->assertEqualsCanonicalizing([$this->own->getId(), $this->foreign->getId()], $ids);

        $ids = array_column($this->members($this->admin, '/api/v1/series'), 'id');
        $this->assertEqualsCanonicalizing([$this->ownSeries->getId(), $this->foreignSeries->getId()], $ids);
    }

    public function testAPersonalApiUserOnlyListsTheirOwnContent(): void
    {
        $this->assertSame([$this->own->getId()], array_column($this->members($this->publisher, '/api/v1/multimedia_objects'), 'id'));
        $this->assertSame([$this->ownSeries->getId()], array_column($this->members($this->publisher, '/api/v1/series'), 'id'));
    }

    public function testAPersonalApiUserCannotFetchContentOfOthers(): void
    {
        $this->get($this->publisher, '/api/v1/multimedia_objects/'.$this->own->getId());

        $this->request($this->publisher, '/api/v1/multimedia_objects/'.$this->foreign->getId());
        $this->assertResponseStatusCodeSame(404);

        $this->request($this->publisher, '/api/v1/series/'.$this->foreignSeries->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testThePeopleDoNotExposeTheirEmail(): void
    {
        foreach ($this->members($this->admin, '/api/v1/people') as $person) {
            $this->assertArrayNotHasKey('email', $person);
        }
    }

    public function testAPersonalUserWithoutApiAccessCannotUseTheLegacyApi(): void
    {
        $this->request($this->teacher, '/api/media/mmobj.json');
        $this->assertResponseStatusCodeSame(403);

        $this->request($this->teacher, '/api/media/mmobj/most_viewed.json');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheLegacyMultimediaObjectsAreScopedForAPersonalApiUser(): void
    {
        $mmobjs = $this->get($this->publisher, '/api/media/mmobj.json')['mmobjs'];
        $this->assertSame([$this->own->getId()], array_column($mmobjs, 'id'));
    }

    #[DataProvider('unscopedLegacyRoutes')]
    public function testOnlyGlobalApiUsersCanUseTheUnscopedLegacyRoutes(string $path): void
    {
        $this->request($this->publisher, $path);
        $this->assertResponseStatusCodeSame(403);

        $this->request($this->admin, $path);
        $this->assertResponseIsSuccessful();
    }

    public static function unscopedLegacyRoutes(): iterable
    {
        yield ['/api/user/users.json'];

        yield ['/api/encoder/jobs.json'];

        yield ['/api/encoder/cpus.json'];

        yield ['/api/encoder/profiles.json'];

        yield ['/api/media/mmobj/num_recorded.json'];

        yield ['/api/media/series/published.json'];

        yield ['/api/media/hours/recorded.json'];

        yield ['/api/media/mmobj/stats.json'];

        yield ['/api/live/lives.json'];

        yield ['/api/live/events.json'];

        yield ['/api/live/live_events.json'];
    }

    #[DataProvider('keywordRoutes')]
    public function testAPersonalApiUserCanUseTheLegacyKeywords(string $path): void
    {
        $this->request($this->publisher, $path);
        $this->assertResponseIsSuccessful();

        $this->request($this->teacher, $path);
        $this->assertResponseStatusCodeSame(403);
    }

    public static function keywordRoutes(): iterable
    {
        yield ['/api/keywords/mmobj.json'];

        yield ['/api/keywords/series.json'];
    }

    private function members(User $user, string $path): array
    {
        return $this->get($user, $path)['member'];
    }

    private function get(User $user, string $path): array
    {
        $this->request($user, $path);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function request(User $user, string $path): void
    {
        $this->client->loginUser($user, 'pumukit');
        $accept = str_starts_with($path, '/api/v1/') ? 'application/ld+json' : 'application/json';
        $this->client->request('GET', $path, [], [], ['HTTP_ACCEPT' => $accept]);
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
}
