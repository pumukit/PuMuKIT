<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 */
#[CoversNothing]
class LoginAttemptFunctionalTest extends WebTestCase
{
    private const USERNAME = 'apiattemptuser';
    private const PASSWORD = 'ApiAttempt!pass';
    private const API = '/api/media/locales.json';

    private KernelBrowser $client;
    private DocumentManager $dm;
    private string $ip;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->ip = $this->randomIp();
        $this->client = static::createClient([], ['REMOTE_ADDR' => $this->ip]);

        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        PumukitTestCase::ensureTestDatabase($this->dm);
        $this->users()->deleteMany([]);

        $user = new User();
        $user->setUsername(self::USERNAME);
        $user->setEmail('apiattemptuser@pumukit.local');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $user->setRoles(['ROLE_ACCESS_API']);
        $user->setEnabled(true);
        $this->dm->persist($user);
        $this->dm->flush();
    }

    protected function tearDown(): void
    {
        $this->users()->deleteMany([]);
        parent::tearDown();
    }

    public function testTheApiAcceptsValidCredentials(): void
    {
        $this->api(self::PASSWORD);

        static::assertResponseIsSuccessful();
    }

    public function testThreeWrongPasswordsBlockTheApi(): void
    {
        $this->failApi(3);

        $this->api(self::PASSWORD);

        static::assertResponseStatusCodeSame(401);
        $this->assertStoredAttempts(3, false);
    }

    public function testABlockFromTheLoginFormBlocksTheApi(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->submitLogin('wrong-password');
        }

        $this->api(self::PASSWORD);

        static::assertResponseStatusCodeSame(401);
        $this->assertStoredAttempts(3, false);
    }

    public function testABlockFromTheApiBlocksTheLoginForm(): void
    {
        $this->failApi(3);

        $this->submitLogin(self::PASSWORD);

        static::assertResponseRedirects('/login');
    }

    public function testTheBlockEndsAfterFiveMinutes(): void
    {
        $this->failApi(3);
        $this->storeAttempts(3, false, '-6 minutes');

        $this->api(self::PASSWORD);

        static::assertResponseIsSuccessful();
        $this->assertStoredAttempts(0, true);
    }

    public function testFailuresOlderThanFiveMinutesStartTheCountAgain(): void
    {
        $this->storeAttempts(2, true, '-10 minutes');

        $this->api('wrong-password');

        static::assertResponseStatusCodeSame(401);
        $this->assertStoredAttempts(1, true);
    }

    public function testASuccessfulLoginResetsTheAttempts(): void
    {
        $this->failApi(2);

        $this->api(self::PASSWORD);

        static::assertResponseIsSuccessful();
        $this->assertStoredAttempts(0, true);
    }

    public function testTheApiThrottlesTheFailuresOfAUserFromAnIp(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->api('wrong-password');
            $this->storeAttempts(0, true, 'now');
        }

        $this->api(self::PASSWORD);
        static::assertResponseStatusCodeSame(401);

        $this->api(self::PASSWORD, $this->randomIp());
        static::assertResponseIsSuccessful();
    }

    private function api(string $password, ?string $ip = null): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('GET', self::API, [], [], [
            'PHP_AUTH_USER' => self::USERNAME,
            'PHP_AUTH_PW' => $password,
            'REMOTE_ADDR' => $ip ?? $this->ip,
        ]);
    }

    private function failApi(int $times): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $this->api('wrong-password');
            static::assertResponseStatusCodeSame(401);
        }
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

    private function storeAttempts(int $attempts, bool $enabled, string $lastAttempt): void
    {
        $this->users()->updateOne(['username' => self::USERNAME], ['$set' => [
            'loginAttempt' => $attempts,
            'enabled' => $enabled,
            'lastLoginAttempt' => new UTCDateTime(new \DateTime($lastAttempt)),
        ]]);
    }

    private function assertStoredAttempts(int $attempts, bool $enabled): void
    {
        $stored = $this->users()->findOne(['username' => self::USERNAME]);

        $this->assertSame($attempts, $stored['loginAttempt']);
        $this->assertSame($enabled, $stored['enabled']);
    }

    private function users(): Collection
    {
        return $this->dm->getDocumentCollection(User::class);
    }

    private function randomIp(): string
    {
        return sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254));
    }
}
