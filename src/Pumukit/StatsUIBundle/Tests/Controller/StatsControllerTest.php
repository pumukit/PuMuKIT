<?php

declare(strict_types=1);

namespace Pumukit\StatsUIBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class StatsControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('statsadmin');
        $user->setEmail('statsadmin@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_SCOPE_GLOBAL', 'ROLE_ACCESS_STATS']);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('statsPages')]
    public function testThePageIsConfiguredThroughTheStatsController(string $uri, string $mode, string $id): void
    {
        $crawler = $this->client->request('GET', $uri);

        $this->assertResponseIsSuccessful();
        $root = $crawler->filter('[data-controller="stats-ui--stats"]');
        $this->assertCount(1, $root);
        $this->assertSame($mode, $root->attr('data-stats-ui--stats-mode-value'));
        $this->assertSame($id, $root->attr('data-stats-ui--stats-id-value'));

        $urls = json_decode((string) $root->attr('data-stats-ui--stats-urls-value'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('/admin/stats/series/__ID__', $urls['seriesStats']);
        $this->assertSame('/api/media/views', $urls['views']);

        $this->assertCount(1, $root->filter('[data-stats-ui--stats-target="dateRange"]'));
        $this->assertCount(3, $root->filter('[data-stats-ui--stats-target="group"]'));
        $this->assertCount('mmobj-id' === $mode ? 1 : 0, $root->filter('template[data-stats-ui--stats-target="previewTemplate"]'));
        $this->assertCount('mmobj-id' === $mode ? 0 : 1, $root->filter('canvas[data-stats-ui--stats-target="mostViewedChart"]'));
        $this->assertCount('mmobj-id' === $mode ? 0 : 1, $root->filter('[data-stats-ui--stats-target="title"]'));
        $this->assertStringNotContainsString('nv.addGraph', (string) $this->client->getResponse()->getContent());
    }

    public static function statsPages(): array
    {
        return [
            'series' => ['/admin/stats/series', 'series', ''],
            'objects' => ['/admin/stats/objects', 'mmobj', ''],
            'one series' => ['/admin/stats/series/5f0000000000000000000001', 'series-id', '5f0000000000000000000001'],
            'one object' => ['/admin/stats/objects/5f0000000000000000000002', 'mmobj-id', '5f0000000000000000000002'],
        ];
    }
}
