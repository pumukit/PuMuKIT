<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\CreateUserService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class SecurityFunctionalTest extends WebTestCase
{
    private const USERNAME = 'functionaltestuser';
    private const PASSWORD = 'FunctionalTest!pass';
    private const EMAIL = 'functionaltestuser@pumukit.local';

    private KernelBrowser $client;
    private DocumentManager $dm;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient([], ['REMOTE_ADDR' => $this->randomIp()]);

        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearCollections();

        // A plain ROLE_USER avoids the personal-series side effects triggered on
        // login for users with the AUTO_CREATE_PERSONAL_SERIES permission, keeping
        // the test focused on the authentication flow.
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setUsername(self::USERNAME);
        $user->setEmail(self::EMAIL);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $user->setEnabled(true);
        $this->dm->persist($user);
        $this->dm->flush();
    }

    protected function tearDown(): void
    {
        $this->clearCollections();
        parent::tearDown();
    }

    public function testCreateUserStoresHashedPassword(): void
    {
        // A default permission profile is required to create a user through the service.
        $profile = new PermissionProfile();
        $profile->setName('functional-test-default');
        $profile->setDefault(true);
        $profile->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($profile);
        $this->dm->flush();

        static::getContainer()->get(CreateUserService::class)
            ->createSuperAdmin('createdadmin', 'Created!pass', 'createdadmin@pumukit.local')
        ;

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'createdadmin']);

        static::assertInstanceOf(User::class, $user);
        static::assertTrue($user->isEnabled());
        static::assertNotEmpty($user->getPassword());
        static::assertNotSame('Created!pass', $user->getPassword());
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->submitLogin(self::USERNAME, self::PASSWORD);
        static::assertResponseRedirects('/');

        // An authenticated user hitting /login is redirected to the homepage.
        $this->client->request('GET', '/login');
        static::assertResponseRedirects('/');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->submitLogin(self::USERNAME, 'wrong-password');
        static::assertResponseRedirects('/login');
    }

    public function testLogout(): void
    {
        $this->submitLogin(self::USERNAME, self::PASSWORD);
        static::assertResponseRedirects('/');

        $this->client->request('GET', '/logout');

        // Once logged out, /login renders the form again instead of redirecting.
        $this->client->request('GET', '/login');
        static::assertResponseIsSuccessful();
    }

    private function submitLogin(string $username, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('#login_form')->form();
        $form['username'] = $username;
        $form['password'] = $password;
        $this->client->submit($form);
    }

    private function clearCollections(): void
    {
        $this->dm->getDocumentCollection(User::class)->deleteMany([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->deleteMany([]);
        $this->dm->flush();
    }

    private function randomIp(): string
    {
        return sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254));
    }
}
