<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversNothing]
class PlaylistTablesTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private Series $playlist;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('playlists');
        $user->setEmail('playlists@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($user);

        $this->playlist = new Series();
        $this->playlist->setNumericalID(1);
        $this->playlist->setType(Series::TYPE_PLAYLIST);
        $this->dm->persist($this->playlist);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    public function testEveryRowOfAnEmptyPlaylistListSpansTheHeader(): void
    {
        $this->dm->remove($this->playlist);
        $this->dm->flush();

        $crawler = $this->request('pumukitnewadmin_playlist_list');

        $this->assertSelectorTextContains('tbody', 'There are no playlist with those values.');
        $this->assertEveryRowSpansTheHeader($crawler);
    }

    public function testEveryRowOfAnEmptyPlaylistSpansTheHeader(): void
    {
        $this->assertEveryRowSpansTheHeader($this->list());
    }

    public function testEveryRowOfAPlaylistWithVideosSpansTheHeader(): void
    {
        $factory = static::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries();
        for ($i = 0; $i < 2; ++$i) {
            $multimediaObject = $factory->createMultimediaObject($series);
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
            $this->playlist->getPlaylist()->addMultimediaObject($multimediaObject);
        }
        $this->dm->flush();

        $crawler = $this->list();

        $this->assertCount(2, $crawler->filter('tr.mm_listed'));
        $this->assertEveryRowSpansTheHeader($crawler);
    }

    private function list(): Crawler
    {
        return $this->request('pumukitnewadmin_playlistmms_list', ['id' => $this->playlist->getId()]);
    }

    private function request(string $route, array $parameters = []): Crawler
    {
        $crawler = $this->client->xmlHttpRequest('GET', static::getContainer()->get('router')->generate($route, $parameters));
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function assertEveryRowSpansTheHeader(Crawler $crawler): void
    {
        $columns = static fn (Crawler $row): int => array_sum($row->filter('th, td')->each(static fn (Crawler $cell): int => (int) ($cell->attr('colspan') ?? 1)));
        $header = $columns($crawler->filter('thead tr')->first());

        foreach ($crawler->filter('tbody > tr, tfoot > tr')->each(static fn (Crawler $row): Crawler => $row) as $position => $row) {
            $this->assertSame($header, $columns($row), sprintf('Row %d of the body or footer', $position + 1));
        }
    }
}
