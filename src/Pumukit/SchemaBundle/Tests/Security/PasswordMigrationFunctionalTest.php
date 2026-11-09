<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\CreateUserService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;

/**
 * @internal
 */
#[CoversNothing]
class PasswordMigrationFunctionalTest extends WebTestCase
{
    private const USERNAME = 'migrationuser';
    private const PASSWORD = 'Migration!pass';
    private const API = '/api/media/locales.json';

    private KernelBrowser $client;
    private DocumentManager $dm;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient([], ['REMOTE_ADDR' => sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254))]);
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        PumukitTestCase::ensureTestDatabase($this->dm);
        $this->users()->deleteMany([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->deleteMany([]);
    }

    protected function tearDown(): void
    {
        $this->users()->deleteMany([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->deleteMany([]);
        parent::tearDown();
    }

    #[DataProvider('legacyHashes')]
    public function testTheLoginFormMigratesALegacyHash(MessageDigestPasswordHasher $legacy, ?string $salt): void
    {
        $this->createUser($legacy->hash(self::PASSWORD, $salt), $salt);

        $this->submitLogin(self::PASSWORD);

        static::assertResponseRedirects('/');
        $this->assertMigrated();

        $this->submitLogin(self::PASSWORD);
        static::assertResponseRedirects('/');
    }

    #[DataProvider('legacyHashes')]
    public function testTheApiMigratesALegacyHash(MessageDigestPasswordHasher $legacy, ?string $salt): void
    {
        $this->createUser($legacy->hash(self::PASSWORD, $salt), $salt);

        $this->api(self::PASSWORD);

        static::assertResponseIsSuccessful();
        $this->assertMigrated();

        $this->api(self::PASSWORD);
        static::assertResponseIsSuccessful();
    }

    public static function legacyHashes(): iterable
    {
        yield 'sha512 with a salt' => [new MessageDigestPasswordHasher('sha512', true, 5000), 'a1b2c3d4e5f6'];

        yield 'sha512 without a salt' => [new MessageDigestPasswordHasher('sha512', true, 5000), null];

        yield 'md5 of the CAS projects' => [new MessageDigestPasswordHasher('md5', false, 1), null];
    }

    public function testAWrongPasswordKeepsTheLegacyHash(): void
    {
        $hash = (new MessageDigestPasswordHasher('md5', false, 1))->hash(self::PASSWORD);
        $this->createUser($hash, null);

        $this->api('wrong-password');

        static::assertResponseStatusCodeSame(401);
        $this->assertSame($hash, $this->stored()['password']);
    }

    public function testACurrentHashIsNotRewritten(): void
    {
        $this->createSuperAdmin();
        $hash = $this->stored()['password'];

        $this->api(self::PASSWORD);

        static::assertResponseIsSuccessful();
        $this->assertSame($hash, $this->stored()['password']);
    }

    public function testANewUserGetsABcryptHash(): void
    {
        $this->createSuperAdmin();

        $this->assertStringStartsWith('$2y$', $this->stored()['password']);
    }

    private function createUser(string $hash, ?string $salt): void
    {
        $user = new User();
        $user->setUsername(self::USERNAME);
        $user->setEmail('migrationuser@pumukit.local');
        $user->setPassword($hash);
        $user->setSalt($salt);
        $user->setRoles(['ROLE_ACCESS_API']);
        $user->setEnabled(true);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    private function createSuperAdmin(): void
    {
        $profile = new PermissionProfile();
        $profile->setName('migration-test-default');
        $profile->setDefault(true);
        $profile->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($profile);
        $this->dm->flush();

        static::getContainer()->get(CreateUserService::class)->createSuperAdmin(self::USERNAME, self::PASSWORD, 'migrationuser@pumukit.local');
        $this->dm->clear();
    }

    private function assertMigrated(): void
    {
        $stored = $this->stored();

        $this->assertStringStartsWith('$2y$', $stored['password']);
        $this->assertNull($stored['salt'] ?? null);
    }

    private function api(string $password): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('GET', self::API, [], [], ['PHP_AUTH_USER' => self::USERNAME, 'PHP_AUTH_PW' => $password]);
    }

    private function submitLogin(string $password): void
    {
        $this->client->getCookieJar()->clear();
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('#login_form')->form();
        $form['username'] = self::USERNAME;
        $form['password'] = $password;
        $this->client->submit($form);
    }

    private function stored(): array|object
    {
        return $this->users()->findOne(['username' => self::USERNAME]);
    }

    private function users(): Collection
    {
        return $this->dm->getDocumentCollection(User::class);
    }
}
