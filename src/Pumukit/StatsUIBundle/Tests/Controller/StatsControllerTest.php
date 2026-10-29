<?php

declare(strict_types=1);

namespace Pumukit\StatsUIBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class StatsControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $factory = static::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries(null, ['en' => 'Stats series']);
        $this->multimediaObject = $factory->createMultimediaObject($series);
        $this->multimediaObject->setTitle('Stats video', 'en');
        $this->dm->flush();
    }

    #[DataProvider('modes')]
    public function testThePanelIsConfiguredForEachMode(string $page, string $series, string $multimediaObject, int $topLists, int $kpis): void
    {
        $this->client->loginUser($this->user(['ROLE_SUPER_ADMIN', PermissionProfile::SCOPE_GLOBAL, 'ROLE_ACCESS_STATS']), 'pumukit');
        $this->client->request('GET', $this->url($page));

        $this->assertResponseIsSuccessful();
        $panel = $this->client->getCrawler()->filter('[data-controller="stats-ui--panel"]');
        $this->assertCount(1, $panel);
        $this->assertSame($this->id($series), $panel->attr('data-stats-series'));
        $this->assertSame($this->id($multimediaObject), $panel->attr('data-stats-multimedia-object'));
        $this->assertSame('/api/v1/stats/summary', json_decode($panel->attr('data-stats-reports'), true)['summary']);
        $this->assertCount($topLists, $panel->filter('[data-controller="stats-ui--top"]'));
        $this->assertCount($kpis, $panel->filter('.stats-kpi'));
        $this->assertCount(1, $panel->filter('[data-controller="stats-ui--timeline"] canvas'));
        $this->assertCount(2, $panel->filter('[data-controller="stats-ui--bars"]'));
        $this->assertCount(1, $panel->filter('[data-controller="stats-ui--heatmap"]'));
    }

    public static function modes(): iterable
    {
        yield 'overview' => ['/admin/stats', '', '', 2, 5];

        yield 'one series' => ['/admin/stats/series/{series}', 'series', '', 1, 4];

        yield 'one object' => ['/admin/stats/objects/{mm}', '', 'mm', 0, 2];
    }

    public function testAPersonalUserCannotOpenTheStatsOfOtherObjects(): void
    {
        $this->client->loginUser($this->user([PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_STATS']), 'pumukit');

        $this->client->request('GET', $this->url('/admin/stats/objects/{mm}'));
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', $this->url('/admin/stats/series/{series}'));
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/stats');
        $this->assertResponseIsSuccessful();
    }

    public function testUnknownIdsAreNotFound(): void
    {
        $this->client->loginUser($this->user(['ROLE_SUPER_ADMIN', 'ROLE_ACCESS_STATS']), 'pumukit');

        $this->client->request('GET', '/admin/stats/objects/5f0000000000000000000002');
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/stats/series/not-an-id');
        $this->assertResponseStatusCodeSame(404);
    }

    private function url(string $page): string
    {
        return str_replace(['{series}', '{mm}'], [$this->id('series'), $this->id('mm')], $page);
    }

    private function id(string $which): string
    {
        return match ($which) {
            'series' => (string) $this->multimediaObject->getSeries()->getId(),
            'mm' => (string) $this->multimediaObject->getId(),
            default => '',
        };
    }

    private function user(array $roles): User
    {
        $user = new User();
        $user->setUsername('stats-'.md5(implode(',', $roles)));
        $user->setEmail('stats@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles($roles);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }
}
