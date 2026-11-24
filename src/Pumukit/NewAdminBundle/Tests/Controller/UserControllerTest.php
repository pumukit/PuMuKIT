<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class UserControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->user('superadmin', User::ORIGIN_LOCAL, ['ROLE_SUPER_ADMIN']);
        $manager = $this->user('manager', User::ORIGIN_LOCAL, [PermissionProfile::SCOPE_GLOBAL, 'ROLE_ACCESS_ADMIN_USERS']);
        $this->client->loginUser($manager, 'pumukit');
    }

    public function testRefusingToDeleteAnExternalUserKeepsItsPerson(): void
    {
        $external = $this->user('external', 'cas');

        $this->client->request('DELETE', '/admin/user/'.$external->getId().'/delete');

        $this->assertResponseStatusCodeSame(409);
        $this->assertTheUserKeepsItsPerson('external');
    }

    public function testRefusingABatchDeleteKeepsThePersonOfEveryUser(): void
    {
        $local = $this->user('local', User::ORIGIN_LOCAL);
        $external = $this->user('external', 'cas');

        $this->client->request('DELETE', '/admin/user/batchdelete?'.http_build_query(['ids' => json_encode([$local->getId(), $external->getId()])]));

        $this->assertResponseStatusCodeSame(409);
        $this->assertTheUserKeepsItsPerson('local');
        $this->assertTheUserKeepsItsPerson('external');
    }

    public function testDeletingALocalUserUnlinksItsPerson(): void
    {
        $local = $this->user('local', User::ORIGIN_LOCAL);

        $this->client->request('DELETE', '/admin/user/'.$local->getId().'/delete');

        $this->assertResponseRedirects();
        $this->dm->clear();
        $this->assertNull($this->dm->getRepository(User::class)->findOneBy(['username' => 'local']));
        $person = $this->dm->getRepository(Person::class)->findOneBy(['name' => 'local']);
        $this->assertNotNull($person);
        $this->assertNull($person->getUser());
    }

    public function testAUserManagerCanEditTheProfileOfAnExternalUser(): void
    {
        $external = $this->user('external', 'cas');
        $local = $this->user('local', User::ORIGIN_LOCAL);

        $crawler = $this->client->request('GET', '/admin/user/list');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a[href="/admin/user/'.$external->getId().'/edit"][title^="Edit non local user"]'));
        $this->assertCount(1, $crawler->filter('a[href="/admin/user/'.$local->getId().'/edit"][title^="Edit User"]'));
    }

    private function assertTheUserKeepsItsPerson(string $username): void
    {
        $this->dm->clear();
        $person = $this->dm->getRepository(Person::class)->findOneBy(['name' => $username]);
        $this->assertNotNull($person);
        $this->assertSame($username, $person->getUser()?->getUsername());
    }

    private function user(string $username, string $origin, array $roles = []): User
    {
        $person = new Person();
        $person->setName($username);
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setEnabled(true);
        $user->setOrigin($origin);
        $user->setRoles($roles);
        $user->setPerson($person);
        $person->setUser($user);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }
}
