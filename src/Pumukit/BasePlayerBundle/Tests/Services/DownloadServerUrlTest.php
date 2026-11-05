<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[CoversNothing]
final class DownloadServerUrlTest extends TestCase
{
    private const SERVER = 'https://dls.example.org';

    #[DataProvider('urls')]
    public function testItRecognisesTheUrlsOfTheDownloadServer(?string $server, string $url, bool $expected): void
    {
        self::assertSame($expected, $this->service($server)->isOnDownloadServer($url));
    }

    public static function urls(): iterable
    {
        yield 'its file' => [self::SERVER, self::SERVER.'/pumukit/a.mp4', true];

        yield 'with a final slash in the setting' => [self::SERVER.'/', self::SERVER.'/pumukit/a.mp4', true];

        yield 'another host with the same start' => [self::SERVER, self::SERVER.'.evil.org/pumukit/a.mp4', false];

        yield 'another site' => [self::SERVER, 'https://example.org/pumukit/a.mp4', false];

        yield 'not configured' => [null, self::SERVER.'/pumukit/a.mp4', false];

        yield 'empty setting' => ['', self::SERVER.'/pumukit/a.mp4', false];
    }

    public function testItSignsThePathOfTheFile(): void
    {
        $url = $this->service(self::SERVER)->signForDownloadServer(self::SERVER.'/pumukit/my%20video.mp4');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith(self::SERVER.'/pumukit/my%20video.mp4?token=', $url);
        self::assertSame('/pumukit/my video.mp4', $query['resource']);
        self::assertTrue((new SecureTokenService('secret'))->validateToken($query['token'], $query['resource'], (int) $query['expires']));
    }

    public function testItLeavesTheOtherUrlsAsTheyAre(): void
    {
        $url = 'https://bucket.example.org/a.mp4?X-Amz-Signature=abc';

        self::assertSame($url, $this->service(self::SERVER)->signForDownloadServer($url));
    }

    private function service(?string $server): TrackUrlService
    {
        return new TrackUrlService($this->createStub(UrlGeneratorInterface::class), new SecureTokenService('secret'), $server);
    }
}
