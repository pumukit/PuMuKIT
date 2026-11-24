<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class AuthControllerTest extends PumukitTestCase
{
    #[DataProvider('targets')]
    public function testTheRefererParameterOnlyRedirectsWithinTheSite(string $referer, string $expected): void
    {
        $client = $this->loggedInClient();
        $client->request('GET', '/auth', ['referer' => $referer]);

        $this->assertResponseRedirects($expected);
    }

    #[DataProvider('targets')]
    public function testTheRefererHeaderOnlyRedirectsWithinTheSite(string $referer, string $expected): void
    {
        $client = $this->loggedInClient();
        $client->request('GET', '/auth', [], [], ['HTTP_REFERER' => $referer]);

        $this->assertResponseRedirects($expected);
    }

    public static function targets(): iterable
    {
        yield 'relative path' => ['/series/1?page=2', '/series/1?page=2'];

        yield 'same host' => ['http://localhost/video/1', 'http://localhost/video/1'];

        yield 'other host' => ['https://evil.example/login', '/'];

        yield 'protocol relative' => ['//evil.example/login', '/'];

        yield 'backslash' => ['/\evil.example/login', '/'];

        yield 'tab before slash' => ["/\t/evil.example/login", '/'];

        yield 'javascript' => ['javascript:alert(1)', '/'];

        yield 'same host other scheme' => ['ftp://localhost/file', '/'];

        yield 'userinfo' => ['http://localhost@evil.example/', '/'];
    }

    private function loggedInClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('viewer');
        $user->setEmail('viewer@pumukit.local');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_USER']);
        $this->dm->persist($user);
        $this->dm->flush();

        $client->loginUser($user, 'pumukit');

        return $client;
    }
}
