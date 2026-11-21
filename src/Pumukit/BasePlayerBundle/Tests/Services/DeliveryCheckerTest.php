<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Pumukit\BasePlayerBundle\Controller\DownloadAuthController;
use Pumukit\BasePlayerBundle\Services\DeliveryChecker;
use Pumukit\BasePlayerBundle\Services\DeliveryCheckResult;
use Pumukit\BasePlayerBundle\Services\DeliveryProbe;
use Pumukit\BasePlayerBundle\Services\DeliveryResponse;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversNothing]
class DeliveryCheckerTest extends TestCase
{
    private const array TOKEN_CHECKS = [
        'Download server denies a file without a token',
        'Download server denies the token of another file',
        'Download server denies a changed resource',
        'Download server denies an expired token',
        'Download server denies a token of another secret',
        'Download server denies a player token',
    ];

    private const array FILES_TOKEN_CHECKS = [
        'Download server denies a /files/ download without a token',
        'Download server denies the token of another file on /files/',
    ];

    private const array ROBOTS_CHECKS = [
        'Download server sends X-Robots-Tag on /pumukit/',
        'Download server sends X-Robots-Tag on /files/',
        'Download server sends X-Robots-Tag on /hls/',
    ];

    private const array HLS_TOKEN_CHECKS = [
        'Download server denies an HLS token of another directory',
        'Download server denies an HLS token on a directory it was not signed for',
        'Download server denies an expired HLS token',
    ];

    private const array SERVED_CHECKS = [
        'Download server serves a signed file',
        'Download server serves a byte range',
        'Download server forces the download',
        'Download server serves a signed download through /files/',
        'Download server serves a signed master through /files/',
        'Download server serves an HLS playlist with the directory token',
        'Download server serves an HLS segment with the directory token',
        'Download server serves an HLS segment of a subdirectory',
    ];

    private string $storageDir;
    private string $downloadsDir;
    private SecureTokenService $secureTokenService;
    private DeliveryChecker $checker;

    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir().'/pumukit-delivery-'.bin2hex(random_bytes(6));
        $this->downloadsDir = $this->storageDir.'/downloads';
        mkdir($this->downloadsDir, 0o755, true);
        $this->secureTokenService = new SecureTokenService('delivery-test-secret');
        $this->checker = new DeliveryChecker($this->secureTokenService, $this->downloadsDir, 'https://dls.localhost/pumukit', 'https://localhost');
    }

    protected function tearDown(): void
    {
        rmdir($this->downloadsDir);
        rmdir($this->storageDir);
    }

    public function testASoundDeploymentPassesEveryCheck(): void
    {
        $results = $this->checker->check($this->deployment());

        $this->assertNotEmpty($results);
        $this->assertSame([], $this->failed($results));
    }

    #[DataProvider('brokenDeployments')]
    public function testABrokenDeploymentFailsItsChecks(string $breakage, array $expected): void
    {
        $failed = $this->failed($this->checker->check($this->deployment([$breakage])));

        sort($expected);
        sort($failed);
        $this->assertSame($expected, $failed);
    }

    public static function brokenDeployments(): iterable
    {
        yield 'download server without auth_request' => ['no-token', [...self::TOKEN_CHECKS, ...self::FILES_TOKEN_CHECKS, ...self::HLS_TOKEN_CHECKS]];

        yield 'storage open on the frontend' => ['open-storage', [
            'Frontend denies the storage directory',
            'Frontend denies the storage directory with a valid token',
            'Frontend denies the files outside the downloads',
        ]];

        yield 'validator failing' => ['validator-500', [
            ...self::SERVED_CHECKS,
            ...self::ROBOTS_CHECKS,
            ...self::TOKEN_CHECKS,
            ...self::FILES_TOKEN_CHECKS,
            ...self::HLS_TOKEN_CHECKS,
            'Download server denies CORS to another origin',
            'Download server allows CORS to the frontend on /hls/',
        ]];

        yield 'HLS cache keyed by the token only' => ['hls-cache-token-only', ['Download server denies an HLS token on a directory it was not signed for']];

        yield 'robots header missing' => ['no-robots', self::ROBOTS_CHECKS];

        yield 'HLS without validation' => ['hls-no-token', self::HLS_TOKEN_CHECKS];

        yield 'files open to the whole storage' => ['files-wide', [
            'Download server denies the inbox through /files/',
            'Download server denies the temporary files through /files/',
        ]];

        yield 'CORS open to any origin' => ['cors-any', ['Download server denies CORS to another origin']];

        yield 'CORS closed to the frontend' => ['cors-none', ['Download server allows CORS to the frontend on /hls/']];
    }

    public function testAnUnreachableServerFailsEveryCheck(): void
    {
        $probe = new class extends DeliveryProbe {
            public function fetch(string $url, array $headers = []): DeliveryResponse
            {
                return new DeliveryResponse(0, [], '', 'Connection refused');
            }
        };

        $results = $this->checker->check($probe);

        $this->assertCount(count($results), $this->failed($results));
        $this->assertStringContainsString('Connection refused', $results[0]->detail);
    }

    public function testTheOverriddenUrlsAreRequested(): void
    {
        $probe = new class extends DeliveryProbe {
            public array $hosts = [];

            public function fetch(string $url, array $headers = []): DeliveryResponse
            {
                $this->hosts[] = parse_url($url, PHP_URL_HOST);

                return new DeliveryResponse(404);
            }
        };

        $this->checker->check($probe, 'https://vod.example.org/pumukit/', 'https://tv.example.org');

        $hosts = array_values(array_unique($probe->hosts));
        sort($hosts);
        $this->assertSame(['tv.example.org', 'vod.example.org'], $hosts);
    }

    public function testTheTemporaryFilesAreRemoved(): void
    {
        $this->checker->check($this->deployment());

        $this->assertSame(['downloads'], array_values(array_diff(scandir($this->storageDir), ['.', '..'])));
        $this->assertSame([], array_values(array_diff(scandir($this->downloadsDir), ['.', '..'])));
    }

    /**
     * @param list<DeliveryCheckResult> $results
     *
     * @return list<string>
     */
    private function failed(array $results): array
    {
        return array_values(array_map(
            static fn (DeliveryCheckResult $result): string => $result->name,
            array_filter($results, static fn (DeliveryCheckResult $result): bool => !$result->passed)
        ));
    }

    /**
     * @param list<string> $breakages
     */
    private function deployment(array $breakages = []): DeliveryProbe
    {
        return new class(new DownloadAuthController($this->secureTokenService, new NullLogger()), $this->downloadsDir, $breakages) extends DeliveryProbe {
            public function __construct(
                private readonly DownloadAuthController $controller,
                private readonly string $downloadsDir,
                private readonly array $breakages,
            ) {
                parent::__construct();
            }

            public function fetch(string $url, array $headers = []): DeliveryResponse
            {
                $host = (string) parse_url($url, PHP_URL_HOST);
                $path = (string) parse_url($url, PHP_URL_PATH);
                $query = (string) parse_url($url, PHP_URL_QUERY);
                $requestHeaders = [];
                foreach ($headers as $header) {
                    [$name, $value] = explode(':', $header, 2);
                    $requestHeaders[strtolower($name)] = trim($value);
                }

                if ('localhost' === $host) {
                    if ($this->broken('open-storage') && str_starts_with($path, '/storage/')) {
                        return $this->file(dirname($this->downloadsDir).substr($path, strlen('/storage')), $query, $requestHeaders);
                    }

                    return new DeliveryResponse(404);
                }

                if (str_starts_with($path, '/opencast/')) {
                    return new DeliveryResponse(401);
                }

                if (str_starts_with($path, '/files/')) {
                    return $this->files($path, $query, $requestHeaders);
                }

                if (str_starts_with($path, '/hls/')) {
                    return $this->hls($path, $query, $requestHeaders);
                }

                if (str_contains($path, '..') || false !== stripos($path, '%2e') || !str_starts_with($path, '/pumukit/')) {
                    return new DeliveryResponse(404);
                }

                if (!$this->broken('no-token')) {
                    $request = Request::create('/download/validate-token?'.$query);
                    $request->headers->set('X-Original-URI', $path.'?'.$query);
                    $status = $this->broken('validator-500') ? 500 : $this->controller->validateToken($request)->getStatusCode();
                    if (200 !== $status) {
                        return new DeliveryResponse($status);
                    }
                }

                return $this->file($this->downloadsDir.'/'.rawurldecode(substr($path, strlen('/pumukit/'))), $query, $requestHeaders);
            }

            private function files(string $path, string $query, array $requestHeaders): DeliveryResponse
            {
                $pattern = $this->broken('files-wide') ? '#^/files/((?:downloads|masters|inbox|tmp)/.+)$#' : '#^/files/((?:downloads|masters)/.+)$#';
                if (str_contains($path, '..') || false !== stripos($path, '%2e') || 1 !== preg_match($pattern, $path, $matches)) {
                    return new DeliveryResponse(404);
                }

                if (!$this->broken('no-token')) {
                    $request = Request::create('/download/validate-token?'.$query);
                    $request->headers->set('X-Original-URI', $path.'?'.$query);
                    $status = $this->broken('validator-500') ? 500 : $this->controller->validateToken($request)->getStatusCode();
                    if (200 !== $status) {
                        return new DeliveryResponse($status);
                    }
                }

                return $this->file(dirname($this->downloadsDir).'/'.rawurldecode($matches[1]), $query, $requestHeaders);
            }

            private function hls(string $path, string $query, array $requestHeaders): DeliveryResponse
            {
                $pattern = '#^/hls/\d+/[A-Za-z0-9_-]+/files/(downloads/[^/]+/[^/]+/)(.+\.(?:m3u8|ts))$#';
                if (str_contains($path, '..') || false !== stripos($path, '%2e') || 1 !== preg_match($pattern, $path, $matches)) {
                    return new DeliveryResponse(404);
                }

                if (!$this->broken('hls-no-token') && !$this->broken('no-token')) {
                    $request = Request::create('/download/validate-hls');
                    $request->headers->set('X-Original-URI', $path);
                    $cacheKey = explode('/', $path)[3];
                    if ($this->broken('hls-cache-token-only') && isset($this->cache[$cacheKey])) {
                        $status = $this->cache[$cacheKey];
                    } else {
                        $status = $this->broken('validator-500') ? 500 : $this->controller->validateHls($request)->getStatusCode();
                        $this->cache[$cacheKey] = $status;
                    }
                    if (200 !== $status) {
                        return new DeliveryResponse($status);
                    }
                }

                return $this->file(dirname($this->downloadsDir).'/'.$matches[1].rawurldecode($matches[2]), $query, $requestHeaders);
            }

            private array $cache = [];

            private function broken(string $breakage): bool
            {
                return in_array($breakage, $this->breakages, true);
            }

            private function file(string $file, string $query, array $requestHeaders): DeliveryResponse
            {
                if (!is_file($file)) {
                    return new DeliveryResponse(404);
                }

                $content = (string) file_get_contents($file);
                parse_str($query, $parameters);
                $responseHeaders = [];
                if (!$this->broken('no-robots')) {
                    $responseHeaders['x-robots-tag'] = 'noindex';
                }
                if ('1' === ($parameters['forcedl'] ?? null)) {
                    $responseHeaders['content-disposition'] = 'attachment';
                }

                $origin = $requestHeaders['origin'] ?? null;
                if (null !== $origin && !$this->broken('cors-none') && ($this->broken('cors-any') || 'https://localhost' === $origin)) {
                    $responseHeaders['access-control-allow-origin'] = $origin;
                }

                if (preg_match('/^bytes=(\d+)-(\d+)$/', $requestHeaders['range'] ?? '', $range)) {
                    return new DeliveryResponse(206, $responseHeaders, substr($content, (int) $range[1], (int) $range[2] - (int) $range[1] + 1));
                }

                return new DeliveryResponse(200, $responseHeaders, $content);
            }
        };
    }
}
