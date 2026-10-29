<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class AdminRequestErrorsTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('requests');
        $user->setEmail('requests@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('searchLists')]
    public function testASearchWithoutTextKeepsTheListWorking(string $search, string $list): void
    {
        $this->client->request('GET', $search);
        $this->assertResponseRedirects();

        $this->client->request('GET', $list);
        $this->assertResponseIsSuccessful();
    }

    public static function searchLists(): iterable
    {
        yield 'series' => ['/admin/series/search', '/admin/series/list'];

        yield 'playlists' => ['/admin/playlist/search', '/admin/playlist/list'];
    }
}
