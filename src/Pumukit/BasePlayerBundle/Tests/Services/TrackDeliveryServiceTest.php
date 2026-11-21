<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[CoversNothing]
class TrackDeliveryServiceTest extends TestCase
{
    private const DLS = 'https://dls';
    private const SECRET = 'a-known-secret';

    private string $root;
    private string $storage;
    private string $outside;
    private SecureTokenService $tokens;
    private object $logger;

    protected function setUp(): void
    {
        $this->root = (string) realpath(sys_get_temp_dir()).'/delivery-'.bin2hex(random_bytes(6));
        $this->storage = $this->root.'/storage';
        $this->outside = $this->root.'/otro';
        mkdir($this->storage.'/downloads', 0777, true);
        mkdir($this->storage.'/masters', 0777, true);
        mkdir($this->storage.'/inbox', 0777, true);
        mkdir($this->outside, 0777, true);
        $this->tokens = new SecureTokenService(self::SECRET, 3600);
        $this->logger = new class extends AbstractLogger {
            public array $levels = [];
            public array $contexts = [];

            public function log($level, $message, array $context = []): void
            {
                $this->levels[] = $level;
                $this->contexts[] = $context;
            }
        };
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testDownloadsFileRedirectsToFiles(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');

        $response = $this->service()->response($track);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertMatchesRegularExpression(
            '#^https://dls/files/downloads/s/a\.mp4\?token=[^&]+&expires=\d+&resource=%2Ffiles%2Fdownloads%2Fs%2Fa\.mp4$#',
            $response->headers->get('Location')
        );
    }

    public function testForceDownloadAddsForcedl(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');

        $response = $this->service()->response($track, true);

        $this->assertStringEndsWith('&forcedl=1', $response->headers->get('Location'));
    }

    public function testMasterRedirectsToFiles(): void
    {
        $track = $this->localTrack('masters/s/a.mov');

        $response = $this->service()->response($track);

        $this->assertStringStartsWith('https://dls/files/masters/s/a.mov?token=', $response->headers->get('Location'));
    }

    public function testSegmentsAreEncoded(): void
    {
        $track = $this->localTrack('downloads/s/a b.mp4');

        $response = $this->service()->response($track);

        $this->assertStringStartsWith('https://dls/files/downloads/s/a%20b.mp4?token=', $response->headers->get('Location'));
        $this->assertStringContainsString('resource=%2Ffiles%2Fdownloads%2Fs%2Fa+b.mp4', $response->headers->get('Location'));
    }

    public function testHlsRedirectsToTheSignedDirectory(): void
    {
        $track = $this->localTrack('downloads/s/j/j.m3u8');

        $response = $this->service()->response($track);

        $this->assertMatchesRegularExpression(
            '#^https://dls/hls/(\d+)/([^/]+)/files/downloads/s/j/j\.m3u8$#',
            $response->headers->get('Location')
        );
        preg_match('#/hls/(\d+)/([^/]+)/#', $response->headers->get('Location'), $m);
        $this->assertTrue($this->tokens->validateToken($m[2], '/files/downloads/s/j/', (int) $m[1]));
    }

    public function testHlsInASharedDirectoryIs503(): void
    {
        foreach (['downloads/s/x.m3u8', 'downloads/s/j/otro.m3u8'] as $relative) {
            $track = $this->localTrack($relative);

            $response = $this->service()->response($track);

            $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
            $this->assertSame('shared HLS directory', $this->service()->undeliverableReason($track));
            $this->assertNull($this->service()->downloadServerUrl($track));
        }
        $this->assertSame(['critical', 'critical'], $this->logger->levels);
    }

    public function testExpiryCoversTheDuration(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4', 7200);

        $url = $this->service()->downloadServerUrl($track);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertGreaterThanOrEqual(time() + 3600 + 7200, (int) $query['expires']);
    }

    public function testHlsExpiryCoversTheDuration(): void
    {
        $track = $this->localTrack('downloads/s/j/j.m3u8', 7200);

        $url = $this->service()->downloadServerUrl($track);

        preg_match('#/hls/(\d+)/#', $url, $m);
        $this->assertGreaterThanOrEqual(time() + 3600 + 7200, (int) $m[1]);
    }

    public function testOldTrackWithStorageUrlUsesThePath(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4', 0, '/storage/downloads/s/a.mp4');

        $response = $this->service()->response($track);

        $this->assertStringStartsWith('https://dls/files/downloads/s/a.mp4?token=', $response->headers->get('Location'));
    }

    public function testPathOutsideTheStorageIs503(): void
    {
        $outside = $this->localTrack('../otro/a.mp4');
        $inbox = $this->localTrack('inbox/a.mp4');

        foreach ([$outside, $inbox] as $track) {
            $response = $this->service()->response($track);

            $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
            $this->assertSame('outside the storage', $this->service()->undeliverableReason($track));
        }
        $this->assertSame(['critical', 'critical'], $this->logger->levels);
    }

    public function testSymlinkCannotEscapeTheStorage(): void
    {
        file_put_contents($this->outside.'/secret.mp4', 'x');
        symlink($this->outside, $this->storage.'/downloads/link');
        $track = $this->localTrack('downloads/link/secret.mp4');

        $this->assertSame('outside the storage', $this->service()->undeliverableReason($track));
    }

    public function testMissingFileIs404(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');
        unlink($this->storage.'/downloads/s/a.mp4');

        $response = $this->service()->response($track);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('missing file', $this->service()->undeliverableReason($track));
        $this->assertSame([], $this->logger->levels);
    }

    public function testNoDownloadServerIs503(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');

        $response = $this->service('')->response($track);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('no download server', $this->service('')->undeliverableReason($track));
        $this->assertSame(['critical'], $this->logger->levels);
        $this->assertSame($track->id(), $this->logger->contexts[0]['track']);
        $this->assertSame($this->storage.'/downloads/s/a.mp4', $this->logger->contexts[0]['path']);
        $this->assertSame('no download server', $this->logger->contexts[0]['reason']);
    }

    public function testUnresolvableStorageDirIsNotDeliverable(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');
        $service = new TrackDeliveryService(
            $this->root.'/does-not-exist',
            self::DLS,
            $this->tokens,
            new TrackUrlService($this->createStub(UrlGeneratorInterface::class), $this->tokens, self::DLS),
            $this->logger
        );

        $response = $service->response($track);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('outside the storage', $service->undeliverableReason($track));
        $this->assertSame(['critical'], $this->logger->levels);
    }

    public function testHlsDirectoryWithUnsafeCharactersIsNotDeliverable(): void
    {
        $track = $this->localTrack('downloads/s x/j x/j x.m3u8');

        $response = $this->service()->response($track);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('shared HLS directory', $this->service()->undeliverableReason($track));
    }

    public function testDeliverableTrackHasNoReason(): void
    {
        $track = $this->localTrack('downloads/s/a.mp4');

        $this->assertNull($this->service()->undeliverableReason($track));
    }

    public function testExternalTrackOnTheDownloadServerIsSigned(): void
    {
        $track = $this->externalTrack('https://dls/files/downloads/s/a.mp4');

        $response = $this->service()->response($track, true);

        $this->assertStringStartsWith('https://dls/files/downloads/s/a.mp4?token=', $response->headers->get('Location'));
        $this->assertStringEndsWith('&forcedl=1', $response->headers->get('Location'));
        $this->assertNull($this->service()->undeliverableReason($track));
    }

    public function testExternalTrackElsewhereKeepsTheQuery(): void
    {
        $plain = $this->externalTrack('https://example.org/video.mp4');
        $withQuery = $this->externalTrack('https://example.org/video.mp4?a=1');

        $this->assertSame('https://example.org/video.mp4?x=2', $this->service()->response($plain, false, 'x=2')->headers->get('Location'));
        $this->assertSame('https://example.org/video.mp4?a=1&x=2', $this->service()->response($withQuery, false, 'x=2')->headers->get('Location'));
        $this->assertSame('https://example.org/video.mp4', $this->service()->response($plain)->headers->get('Location'));
    }

    public function testNeverReturnsABinaryFileResponse(): void
    {
        $tracks = [
            $this->localTrack('downloads/s/a.mp4'),
            $this->localTrack('masters/s/a.mov'),
            $this->localTrack('downloads/s/j/j.m3u8'),
            $this->localTrack('downloads/s/x.m3u8'),
            $this->localTrack('inbox/a.mp4'),
            $this->externalTrack('https://example.org/video.mp4'),
        ];
        $missing = $this->localTrack('downloads/s/gone.mp4');
        unlink($this->storage.'/downloads/s/gone.mp4');
        $tracks[] = $missing;

        foreach ($tracks as $track) {
            $response = $this->service()->response($track);
            $this->assertNotInstanceOf(BinaryFileResponse::class, $response);
            $this->assertNotInstanceOf(StreamedResponse::class, $response);
        }
        $this->assertInstanceOf(RedirectResponse::class, $this->service()->response($tracks[0]));
    }

    private function service(string $downloadServer = self::DLS): TrackDeliveryService
    {
        $router = $this->createStub(UrlGeneratorInterface::class);

        return new TrackDeliveryService(
            $this->storage,
            $downloadServer,
            $this->tokens,
            new TrackUrlService($router, $this->tokens, self::DLS),
            $this->logger
        );
    }

    private function localTrack(string $relative, int $duration = 0, string $url = ''): Track
    {
        $file = $this->storage.'/'.$relative;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, 'x');

        return $this->track(Storage::local(StorageUrl::create($url), Path::create($file)), $duration);
    }

    private function externalTrack(string $url): Track
    {
        return $this->track(Storage::external(StorageUrl::create($url)), 0);
    }

    private function track(Storage $storage, int $duration): Track
    {
        return Track::create(
            'original',
            i18nText::create(['en' => 'd']),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            $storage,
            VideoAudio::create(json_encode(['format' => ['duration' => (string) $duration]]))
        );
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path), ['.', '..']) as $item) {
            $this->remove($path.'/'.$item);
        }
        rmdir($path);
    }
}
