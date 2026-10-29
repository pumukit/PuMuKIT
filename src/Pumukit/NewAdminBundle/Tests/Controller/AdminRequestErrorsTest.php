<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\SeriesStyle;
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

    public function testCreatingASeriesStyleWithoutNameIsRejected(): void
    {
        $this->client->request('GET', '/admin/series/styles/create', ['name' => '  ']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertCount(0, $this->dm->getRepository(SeriesStyle::class)->findAll());
    }

    #[DataProvider('pagesListingSeriesStyles')]
    public function testASeriesStyleWithoutNameDoesNotBreakThePages(string $path): void
    {
        $named = new SeriesStyle();
        $named->setName('Dark');
        $this->dm->persist($named);
        $this->dm->persist(new SeriesStyle());
        $this->dm->flush();

        $this->client->xmlHttpRequest('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    public static function pagesListingSeriesStyles(): iterable
    {
        yield 'styles' => ['/admin/series/styles/'];

        yield 'styles list' => ['/admin/series/styles/list'];
    }

    public function testASeriesStyleWithoutNameIsAnEmptyString(): void
    {
        $this->assertSame('', (string) new SeriesStyle());
    }

    #[DataProvider('requestsWithoutRequiredParameters')]
    public function testAMissingParameterIsABadRequest(string $method, string $path): void
    {
        $this->client->request($method, $path);

        $this->assertResponseStatusCodeSame(400);
    }

    public static function requestsWithoutRequiredParameters(): iterable
    {
        yield 'series info' => ['GET', '/admin/info_series'];

        yield 'series announce' => ['POST', '/admin/series/invertannounce'];

        yield 'multimedia object announce' => ['POST', '/admin/mms/invertannounce'];

        yield 'tag search' => ['GET', '/admin/mms/search'];

        yield 'tag reload' => ['GET', '/admin/mms/reloadtags'];
    }

    public function testTheUserFiltersWorkWithPartialCriteria(): void
    {
        $this->client->request('GET', '/admin/user/', ['criteria' => ['username' => 'requests']]);

        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('removedRoutes')]
    public function testRemovedRoutesAreNotFound(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseStatusCodeSame(404);
    }

    public static function removedRoutes(): iterable
    {
        yield 'series pictures index' => ['/admin/seriespic/'];

        yield 'multimedia object pictures index' => ['/admin/mmspic/'];

        yield 'metadata translation' => ['/admin/metadata/translate'];
    }
}
