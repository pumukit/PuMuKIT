<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;

/**
 * @internal
 *
 * @coversNothing
 */
class SecureTokenServiceTest extends TestCase
{
    private SecureTokenService $service;

    protected function setUp(): void
    {
        $this->service = new SecureTokenService('secret', 3600);
    }

    public function testHlsUrlSignsTheDirectory(): void
    {
        $url = $this->service->generateHlsUrl('https://dls', '/files/downloads/s/j/', 'j.m3u8');

        $this->assertStringStartsWith('https://dls/hls/', $url);
        $this->assertStringEndsWith('/files/downloads/s/j/j.m3u8', $url);
        $this->assertTrue($this->service->validateHlsPath((string) parse_url($url, PHP_URL_PATH)));
    }

    #[DataProvider('qualityLayouts')]
    public function testHlsTokenCoversEveryQualityLayout(string $relative): void
    {
        $this->assertTrue($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/'.$relative));
    }

    public static function qualityLayouts(): array
    {
        return [
            ['index.m3u8'],
            ['data_000.ts'],
            ['index_high.m3u8'],
            ['data_high_000.ts'],
            ['stream_0/stream.m3u8'],
            ['stream_0/data00.ts'],
        ];
    }

    public function testHlsTokenDoesNotOpenOtherVideos(): void
    {
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/k/index.m3u8'));
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j.m3u8'));
    }

    public function testHlsTokenOnlyServesPlaylistsAndSegments(): void
    {
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/video.mp4'));
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/stream_0/video.mp4'));
    }

    public function testHlsPathRejectsTraversal(): void
    {
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/stream_0/../../k/index.m3u8'));
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/./index.m3u8'));
        $this->assertFalse($this->service->validateHlsPath($this->prefix().'/files/downloads/s/j/stream_0//data00.ts'));
    }

    public function testHlsPathRejectsControlCharacters(): void
    {
        $this->assertFalse($this->service->validateHlsPath($this->prefix()."/files/downloads/s/j/a.mp4\0.ts"));
        $this->assertFalse($this->service->validateHlsPath($this->prefix()."/files/downloads/s/j/a\n.ts"));
    }

    public function testTamperedExpiryIsRejected(): void
    {
        $path = (string) parse_url($this->service->generateHlsUrl('https://dls', '/files/downloads/s/j/', 'j.m3u8'), PHP_URL_PATH);
        $tampered = (string) preg_replace_callback('#^/hls/(\d+)/#', static fn (array $m): string => '/hls/'.((int) $m[1] + 1).'/', $path);

        $this->assertNotSame($path, $tampered);
        $this->assertFalse($this->service->validateHlsPath($tampered));
    }

    public function testWrongSecretIsRejected(): void
    {
        $url = (new SecureTokenService('other', 3600))->generateHlsUrl('https://dls', '/files/downloads/s/j/', 'j.m3u8');

        $this->assertFalse($this->service->validateHlsPath((string) parse_url($url, PHP_URL_PATH)));
    }

    public function testValidForExtendsTheExpiry(): void
    {
        $before = time();
        $url = $this->service->generateDownloadUrl('https://dls/files/downloads/s/j/video.mp4', 7200);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertGreaterThanOrEqual($before + 7200, (int) $query['expires']);
    }

    public function testExpiredHlsTokenIsRejected(): void
    {
        $url = $this->service->generateHlsUrl('https://dls', '/files/downloads/s/j/', 'j.m3u8', -10);

        $this->assertFalse($this->service->validateHlsPath((string) parse_url($url, PHP_URL_PATH)));
    }

    private function prefix(): string
    {
        $url = $this->service->generateHlsUrl('https://dls', '/files/downloads/s/j/', 'j.m3u8');

        return (string) preg_replace('#/files/downloads/.*$#', '', (string) parse_url($url, PHP_URL_PATH));
    }
}
