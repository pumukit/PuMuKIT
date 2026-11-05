<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
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
    private string $filesDir;
    private Track $track;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->trackUrlService = static::getContainer()->get('pumukit_baseplayer.trackurl');
        $this->filesDir = static::getContainer()->getParameter('kernel.project_dir').'/tests/files/';

        $this->track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($this->filesDir.'pumukit.mp4')),
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
        unset($_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'], $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL']);
        parent::tearDown();
    }

    public function testTheProxyDeliversTheTrackWhenItSendsTheAccelMapping(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track), server: $this->accelHeaders($this->filesDir));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-Accel-Redirect', rawurlencode('/internal-storage/pumukit.mp4'));
        $this->assertSame('', $this->client->getInternalResponse()->getContent());
        $this->assertStringContainsString('inline', $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testTheProxyDeliversTheDownloadAsAnAttachment(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track, forceDownload: true), server: $this->accelHeaders($this->filesDir));

        $this->assertResponseHeaderSame('X-Accel-Redirect', rawurlencode('/internal-storage/pumukit.mp4'));
        $this->assertStringContainsString('attachment', $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testPhpSendsTheTrackOutsideTheAccelMapping(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track), server: $this->accelHeaders('/srv/other-storage/'));

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-Accel-Redirect');
        $this->assertSame(filesize($this->filesDir.'pumukit.mp4'), strlen($this->client->getInternalResponse()->getContent()));
    }

    public function testPhpSendsTheTrackWithoutTheProxyHeaders(): void
    {
        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($this->track));

        $this->assertResponseIsSuccessful();
        $this->assertResponseNotHasHeader('X-Accel-Redirect');
        $this->assertSame(filesize($this->filesDir.'pumukit.mp4'), strlen($this->client->getInternalResponse()->getContent()));
    }

    public function testAnInvalidTokenIsNotDelivered(): void
    {
        $this->client->request('GET', '/trackfile/'.$this->track->id().'.mp4?token=invalid&expires='.(time() + 60).'&resource='.$this->track->id(), server: $this->accelHeaders($this->filesDir));

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseNotHasHeader('X-Accel-Redirect');
    }

    public function testATrackOfTheDownloadServerIsRedirectedWithTheTokenOfItsFile(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/my%20video.mp4');
        $this->useTheDownloadServer();

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        static::assertStringStartsWith(self::DOWNLOAD_SERVER.'/pumukit/my%20video.mp4?token=', $location);
        static::assertSame('/pumukit/my video.mp4', $query['resource']);
        static::assertArrayNotHasKey('forcedl', $query);
    }

    public function testADownloadFromTheDownloadServerKeepsTheAttachment(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/video.mp4');
        $this->useTheDownloadServer();

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track, forceDownload: true));

        $this->assertResponseRedirects();
        static::assertStringEndsWith('&forcedl=1', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAnInvalidTokenIsNotRedirectedToTheDownloadServer(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/video.mp4');
        $this->useTheDownloadServer();

        $this->client->request('GET', '/trackfile/'.$track->id().'.mp4?token=invalid&expires='.(time() + 60).'&resource='.$track->id());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAPlaylistStaysOnTheTrackfileRoute(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/video.m3u8');
        $this->useTheDownloadServer();

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseIsSuccessful();
    }

    public function testASegmentStaysOnTheTrackfileRoute(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/video.mp4');
        $this->useTheDownloadServer();

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track).'&file=pumukit.mp4');

        $this->assertResponseIsSuccessful();
    }

    public function testATrackIsNotRedirectedWithoutADownloadServer(): void
    {
        $track = $this->addTrack(self::DOWNLOAD_SERVER.'/pumukit/video.mp4');

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseIsSuccessful();
    }

    public function testAnExternalTrackOutsideTheDownloadServerKeepsItsQuery(): void
    {
        $track = $this->addTrack('https://example.org/video.mp4', null);
        $this->useTheDownloadServer();

        $this->client->request('GET', $this->trackUrlService->generateTrackFileUrl($track));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        static::assertStringStartsWith('https://example.org/video.mp4?', $location);
        static::assertSame($track->id(), $query['resource']);
    }

    private function addTrack(string $url, ?string $path = 'pumukit.mp4'): Track
    {
        $track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create($url), null === $path ? null : Path::create($this->filesDir.$path)),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        );
        $this->multimediaObject->addTrack($track);
        $this->dm->flush();

        return $track;
    }

    private function useTheDownloadServer(): void
    {
        $_ENV['PUMUKIT_DOWNLOAD_SERVER_URL'] = $_SERVER['PUMUKIT_DOWNLOAD_SERVER_URL'] = self::DOWNLOAD_SERVER;
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->trackUrlService = static::getContainer()->get('pumukit_baseplayer.trackurl');
    }

    private function accelHeaders(string $storageDir): array
    {
        return [
            'HTTP_X_SENDFILE_TYPE' => 'X-Accel-Redirect',
            'HTTP_X_ACCEL_MAPPING' => $storageDir.'=/internal-storage/',
        ];
    }
}
