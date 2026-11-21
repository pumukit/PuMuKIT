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

    public function testValidForExtendsTheExpiry(): void
    {
        $url = $this->service->generateDownloadUrl('https://dls/files/downloads/s/j/video.mp4', 7200);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertGreaterThanOrEqual(time() + 7200, (int) $query['expires']);
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
