<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Controller;

use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\BasePlayerBundle\Services\TrackfileWithoutTokenPolicy;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class TrackFileControllerTest extends PumukitTestCase
{
    private const DOWNLOAD_SERVER = 'https://dls.example.org';

    private KernelBrowser $client;
    private TrackUrlService $trackUrlService;
    private string $storageDir;
    private Track $track;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/trackfile-'.bin2hex(random_bytes(6));
        mkdir($this->storageDir.'/downloads/serie', 0777, true);
        mkdir($this->storageDir.'/masters', 0777, true);
        $_ENV['PUMUKIT_STORAGE_DIR'] = $_SERVER['PUMUKIT_STORAGE_DIR'] = $this->storageDir;
        $_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'] = $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL'] = self::DOWNLOAD_SERVER;
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->trackUrlService = static::getContainer()->get('pumukit_baseplayer.trackurl');
        file_put_contents($this->storageDir.'/downloads/serie/pumukit.mp4', 'video');

        $this->track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($this->storageDir.'/downloads/serie/pumukit.mp4')),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $series = new Series();
        $series->setNumericalID(1);
        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addTag($tag);
        $multimediaObject->setSeries($series);
        $multimediaObject->addTrack($this->track);
        $this->dm->persist($series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->multimediaObject = $multimediaObject;
    }

    public function tearDown(): void
    {
        unset($_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'], $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL'], $_ENV['PUMUKIT_STORAGE_DIR'], $_SERVER['PUMUKIT_STORAGE_DIR']);
        $this->removeDirectory($this->storageDir);
        parent::tearDown();
    }

    public function testAMp4IsRedirectedToTheDownloadServer(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/downloads/serie/pumukit.mp4?token=', $location);
        static::assertStringNotContainsString('forcedl', $location);
    }

    public function testAForcedDownloadKeepsTheAttachment(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track, forceDownload: true));

        $this->assertResponseRedirects();
        static::assertStringEndsWith('&forcedl=1', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAPlaylistIsRedirectedToTheHlsPath(): void
    {
        $track = $this->addLocalTrack('downloads/abc/def/def.m3u8');

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseRedirects();
        static::assertMatchesRegularExpression(
            '#^'.preg_quote(self::DOWNLOAD_SERVER, '#').'/hls/\d+/[^/]+/files/downloads/abc/def/def\.m3u8$#',
            (string) $this->client->getResponse()->headers->get('Location')
        );
    }

    public function testAFileParameterIsNotDelivered(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track).'&file=data_000.ts');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
    }

    public function testTheTokenOfAnotherTrackDoesNotOpenIt(): void
    {
        $other = $this->addLocalTrack('downloads/serie/other.mp4');

        $this->client->request('GET', '/trackfile/'.$other->id().'.mp4?'.$this->tokenQuery($this->track));
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track).'&file=other.mp4');
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
    }

    public function testAMissingFileIsNotFound(): void
    {
        $url = $this->trackUrlService->generateTrackFileUrl($this->track);
        unlink($this->storageDir.'/downloads/serie/pumukit.mp4');

        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnInvalidTokenIsNotDelivered(): void
    {
        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4?token=invalid&expires='.(time() + 60).'&resource='.$this->track->id());

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
    }

    public function testATrackOfTheDownloadServerIsRedirectedWithTheTokenOfItsFile(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/my%20video.mp4', null);

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/pumukit/my%20video.mp4?token=', $location);
        static::assertSame('/pumukit/my video.mp4', $query['resource']);
        static::assertArrayNotHasKey('forcedl', $query);
    }

    public function testAnExternalTrackOutsideTheDownloadServerKeepsItsQuery(): void
    {
        $track = $this->addTrack('https://example.org/video.mp4', null);

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        static::assertStringStartsWith('https://example.org/video.mp4?', $location);
        static::assertSame($track->id(), $query['resource']);
    }

    public function testASeriesInTheListIsDeliveredWithoutToken(): void
    {
        $this->usePolicy([(string) $this->multimediaObject->getSeries()->getId()], []);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4');

        $this->assertResponseRedirects();
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/downloads/serie/pumukit.mp4?token=', (string) $this->client->getResponse()->headers->get('Location'));
        $records = $this->legacyTrackfileRecords();
        static::assertCount(1, $records);
        static::assertSame('Trackfile served without a valid token', $records[0]['message']);
        static::assertSame('series', $records[0]['context']['reason']);
        static::assertSame('missing', $records[0]['context']['tokenState']);
        static::assertSame($this->track->id(), $records[0]['context']['trackId']);
        static::assertSame((string) $this->multimediaObject->getId(), $records[0]['context']['multimediaObjectId']);
        static::assertSame((string) $this->multimediaObject->getSeries()->getId(), $records[0]['context']['seriesId']);
        static::assertSame('none', $records[0]['context']['refererHost']);
    }

    public function testAnExpiredTokenWithAListedRefererIsDelivered(): void
    {
        $this->usePolicy([], ['example.org']);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4?token=abc&expires='.(time() - 60), [], [], ['HTTP_REFERER' => 'https://campus.EXAMPLE.org/curso']);

        $this->assertResponseRedirects();
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/', (string) $this->client->getResponse()->headers->get('Location'));
        $records = $this->legacyTrackfileRecords();
        static::assertCount(1, $records);
        static::assertSame('referer', $records[0]['context']['reason']);
        static::assertSame('expired', $records[0]['context']['tokenState']);
        static::assertSame('campus.example.org', $records[0]['context']['refererHost']);
    }

    public function testALookalikeRefererIsRejectedAndLogged(): void
    {
        $this->usePolicy([], ['example.org']);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4?token=bad&expires='.(time() + 60), [], [], ['HTTP_REFERER' => 'https://example.org.attacker.example/']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
        $records = $this->legacyTrackfileRecords();
        static::assertCount(1, $records);
        static::assertSame('Trackfile rejected without a valid token', $records[0]['message']);
        static::assertSame('example.org.attacker.example', $records[0]['context']['refererHost']);
        static::assertSame('invalid', $records[0]['context']['tokenState']);
        static::assertArrayNotHasKey('reason', $records[0]['context']);
    }

    public function testEmptyListsRejectAndLogWithoutToken(): void
    {
        $this->usePolicy([], []);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
        $records = $this->legacyTrackfileRecords();
        static::assertCount(1, $records);
        static::assertSame('Trackfile rejected without a valid token', $records[0]['message']);
        static::assertSame('missing', $records[0]['context']['tokenState']);
        static::assertSame('none', $records[0]['context']['refererHost']);
    }

    public function testAListedSeriesDoesNotOpenAnObjectThePlayVoterDenies(): void
    {
        $this->multimediaObject->setStatus(MultimediaObject::STATUS_BLOCKED);
        $this->dm->flush();
        $this->usePolicy([(string) $this->multimediaObject->getSeries()->getId()], ['example.org']);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4', [], [], ['HTTP_REFERER' => 'https://example.org/']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
        static::assertSame([], $this->legacyTrackfileRecords());
    }

    public function testAFileParameterWithAListedSeriesIsNotLoggedAsServed(): void
    {
        $this->usePolicy([(string) $this->multimediaObject->getSeries()->getId()], []);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4?file=x');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
        static::assertSame([], $this->legacyTrackfileRecords());
    }

    public function testNoTokenAndAnUnlistedRefererIsRejectedAndLogged(): void
    {
        $this->usePolicy(['5f9d88b9c5e1a2b3c4d5e6f7'], ['example.org']);

        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4', [], [], ['HTTP_REFERER' => 'https://other.example.net/page']);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('Location');
        $records = $this->legacyTrackfileRecords();
        static::assertCount(1, $records);
        static::assertSame('Trackfile rejected without a valid token', $records[0]['message']);
        static::assertSame('missing', $records[0]['context']['tokenState']);
        static::assertSame('other.example.net', $records[0]['context']['refererHost']);
    }

    public function testAValidTokenLogsNothingInTheLegacyChannel(): void
    {
        $this->usePolicy([], []);

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track));

        $this->assertResponseRedirects();
        static::assertSame([], $this->legacyTrackfileRecords());
    }

    private function usePolicy(array $series, array $refererHosts): void
    {
        $this->client->disableReboot();
        static::getContainer()->set(TrackfileWithoutTokenPolicy::class, new TrackfileWithoutTokenPolicy($series, $refererHosts));
    }

    private function legacyTrackfileRecords(): array
    {
        $handler = $this->client->getContainer()->get('monolog.handler.legacy_trackfile');
        static::assertInstanceOf(TestHandler::class, $handler);

        return array_map(static fn ($record) => ['message' => $record['message'], 'context' => $record['context']], $handler->getRecords());
    }

    private function addLocalTrack(string $relative): Track
    {
        $path = $this->storageDir.'/'.$relative;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, 'data');

        return $this->addTrack('', $path);
    }

    private function addTrack(string $url, ?string $path = null): Track
    {
        $track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create($url), null === $path ? null : Path::create($path)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $this->multimediaObject->addTrack($track);
        $this->dm->flush();

        return $track;
    }

    private function tokenQuery(Track $track): string
    {
        return (string) parse_url($this->trackUrlService->generateTrackFileUrl($track), PHP_URL_QUERY);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
