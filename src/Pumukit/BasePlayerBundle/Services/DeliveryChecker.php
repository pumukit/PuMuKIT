<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

final class DeliveryChecker
{
    private const string FOREIGN_ORIGIN = 'https://pumukit-check.invalid';

    public function __construct(
        private readonly SecureTokenService $secureTokenService,
        private readonly string $downloadsDir,
        private readonly string $downloadsUrl,
        private readonly string $frontendUrl,
    ) {}

    /**
     * @return list<DeliveryCheckResult>
     */
    public function check(DeliveryProbe $probe, ?string $downloadsUrl = null, ?string $frontendUrl = null): array
    {
        $name = 'pumukit-check-'.bin2hex(random_bytes(8));
        $content = bin2hex(random_bytes(32));
        $outsideContent = bin2hex(random_bytes(32));
        $storage = dirname(rtrim($this->downloadsDir, '/'));
        $directory = rtrim($this->downloadsDir, '/').'/'.$name;
        $file = $directory.'/canary.mp4';
        $outsideFile = $storage.'/'.$name.'.txt';
        $canaries = [
            'playlist' => [$directory.'/hls/canary.m3u8', bin2hex(random_bytes(16))],
            'segment' => [$directory.'/hls/data_000.ts', bin2hex(random_bytes(16))],
            'nested' => [$directory.'/hls/stream_0/data00.ts', bin2hex(random_bytes(16))],
            'video' => [$directory.'/hls/canary.mp4', bin2hex(random_bytes(16))],
            'other' => [$directory.'/otro/canary.m3u8', bin2hex(random_bytes(16))],
            'master' => [$storage.'/masters/'.$name.'/canary.mp4', bin2hex(random_bytes(16))],
            'inbox' => [$storage.'/inbox/'.$name.'.txt', bin2hex(random_bytes(16))],
            'tmp' => [$storage.'/tmp/'.$name.'.txt', bin2hex(random_bytes(16))],
        ];
        $created = [];
        foreach (['', '/masters', '/inbox', '/tmp'] as $suffix) {
            if (!is_dir($storage.$suffix)) {
                $created[] = $storage.$suffix;
            }
        }

        try {
            $this->write($file, $content);
            $this->write($outsideFile, $outsideContent);
            foreach ($canaries as [$path, $body]) {
                $this->write($path, $body);
            }

            return $this->run(
                $probe,
                rtrim($downloadsUrl ?? $this->downloadsUrl, '/'),
                rtrim($frontendUrl ?? $this->frontendUrl, '/'),
                $name,
                $content,
                $outsideContent,
                array_map(static fn (array $canary): string => $canary[1], $canaries)
            );
        } finally {
            foreach ([$file, $outsideFile, ...array_column($canaries, 0)] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            foreach ([$directory.'/hls/stream_0', $directory.'/hls', $directory.'/otro', $directory, $storage.'/masters/'.$name, ...array_reverse($created)] as $path) {
                if (is_dir($path) && [] === array_diff(scandir($path), ['.', '..'])) {
                    rmdir($path);
                }
            }
        }
    }

    /**
     * @param array<string, string> $canaries
     *
     * @return list<DeliveryCheckResult>
     */
    private function run(DeliveryProbe $probe, string $downloadsUrl, string $frontendUrl, string $name, string $content, string $outsideContent, array $canaries): array
    {
        $parts = parse_url($downloadsUrl);
        if (!isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException(sprintf('The download server URL "%s" is not valid.', $downloadsUrl));
        }
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $basePath = rtrim($parts['path'] ?? '', '/');

        $fileUrl = $downloadsUrl.'/'.$name.'/canary.mp4';
        $resource = rawurldecode((string) parse_url($fileUrl, PHP_URL_PATH));
        $query = $this->query($this->secureTokenService, $resource);
        $signed = $fileUrl.'?'.$query;
        $otherResource = $basePath.'/'.$name.'/other.mp4';
        $token = $this->secureTokenService->generateToken($resource);
        $forbidden = [$content, $outsideContent, ...array_values($canaries)];

        $downloadResource = '/files/downloads/'.$name.'/canary.mp4';
        $masterResource = '/files/masters/'.$name.'/canary.mp4';
        $hlsDirectory = '/files/downloads/'.$name.'/hls/';
        $otherDirectory = '/files/downloads/'.$name.'/otro/';
        $hlsToken = $this->secureTokenService->generateToken($hlsDirectory);
        $otherToken = $this->secureTokenService->generateToken($otherDirectory);
        $expiredToken = $this->secureTokenService->generateToken($hlsDirectory, time() - 60);

        $results = [
            $this->served('Download server serves a signed file', $probe->fetch($signed), 200, $content),
            $this->served('Download server serves a byte range', $probe->fetch($signed, ['Range: bytes=0-9']), 206, substr($content, 0, 10)),
            $this->forcesDownload($probe->fetch($signed.'&forcedl=1')),
            $this->denied('Download server denies a file without a token', $probe->fetch($fileUrl), $forbidden),
            $this->denied('Download server denies the token of another file', $probe->fetch($fileUrl.'?'.$this->query($this->secureTokenService, $otherResource)), $forbidden),
            $this->denied('Download server denies a changed resource', $probe->fetch($fileUrl.'?'.http_build_query(['token' => $token['token'], 'expires' => $token['expires'], 'resource' => $otherResource])), $forbidden),
            $this->denied('Download server denies an expired token', $probe->fetch($fileUrl.'?'.$this->query($this->secureTokenService, $resource, time() - 60)), $forbidden),
            $this->denied('Download server denies a token of another secret', $probe->fetch($fileUrl.'?'.$this->query(new SecureTokenService(bin2hex(random_bytes(32))), $resource)), $forbidden),
            $this->denied('Download server denies a player token', $probe->fetch($fileUrl.'?'.$this->query($this->secureTokenService, bin2hex(random_bytes(12)))), $forbidden),
            $this->served('Download server serves a signed download through /files/', $probe->fetch($origin.$downloadResource.'?'.$this->query($this->secureTokenService, $downloadResource)), 200, $content),
            $this->served('Download server serves a signed master through /files/', $probe->fetch($origin.$masterResource.'?'.$this->query($this->secureTokenService, $masterResource)), 200, $canaries['master']),
            $this->served('Download server serves an HLS playlist with the directory token', $probe->fetch($this->hlsUrl($origin, $hlsToken, $hlsDirectory, 'canary.m3u8')), 200, $canaries['playlist']),
            $this->served('Download server serves an HLS segment with the directory token', $probe->fetch($this->hlsUrl($origin, $hlsToken, $hlsDirectory, 'data_000.ts')), 200, $canaries['segment']),
            $this->served('Download server serves an HLS segment of a subdirectory', $probe->fetch($this->hlsUrl($origin, $hlsToken, $hlsDirectory, 'stream_0/data00.ts')), 200, $canaries['nested']),
            $this->denied('Download server denies a /files/ download without a token', $probe->fetch($origin.$downloadResource), $forbidden),
            $this->denied('Download server denies the token of another file on /files/', $probe->fetch($origin.$downloadResource.'?'.$this->query($this->secureTokenService, '/files/downloads/'.$name.'/other.mp4')), $forbidden),
            $this->denied('Download server denies the inbox through /files/', $probe->fetch($origin.'/files/inbox/'.$name.'.txt?'.$this->query($this->secureTokenService, '/files/inbox/'.$name.'.txt')), $forbidden),
            $this->denied('Download server denies the temporary files through /files/', $probe->fetch($origin.'/files/tmp/'.$name.'.txt?'.$this->query($this->secureTokenService, '/files/tmp/'.$name.'.txt')), $forbidden),
            $this->denied('Download server denies an HLS token of another directory', $probe->fetch($this->hlsUrl($origin, $otherToken, $hlsDirectory, 'canary.m3u8')), $forbidden),
            $this->denied('Download server denies an HLS token on a directory it was not signed for', $probe->fetch($this->hlsUrl($origin, $hlsToken, $otherDirectory, 'canary.m3u8')), $forbidden),
            $this->denied('Download server denies an mp4 with an HLS directory token', $probe->fetch($this->hlsUrl($origin, $hlsToken, $hlsDirectory, 'canary.mp4')), $forbidden),
            $this->denied('Download server denies an expired HLS token', $probe->fetch($this->hlsUrl($origin, $expiredToken, $hlsDirectory, 'canary.m3u8')), $forbidden),
        ];

        $results[] = $this->noindex('Download server sends X-Robots-Tag on /pumukit/', $probe->fetch($signed));
        $results[] = $this->noindex('Download server sends X-Robots-Tag on /files/', $probe->fetch($origin.$downloadResource.'?'.$this->query($this->secureTokenService, $downloadResource)));
        $results[] = $this->noindex('Download server sends X-Robots-Tag on /hls/', $probe->fetch($this->hlsUrl($origin, $hlsToken, $hlsDirectory, 'canary.m3u8')));

        foreach (['../', '%2e%2e/'] as $dots) {
            $traversal = '/files/downloads/'.$name.'/'.$dots.$dots.'inbox/'.$name.'.txt';
            $results[] = $this->denied(
                sprintf('Download server denies a path traversal through /files/ (%s)', $dots),
                $probe->fetch($origin.$traversal.'?'.$this->query($this->secureTokenService, rawurldecode($traversal))),
                $forbidden
            );
        }

        foreach (['/../', '/%2e%2e/', '/'.$name.'/../../', '../'] as $traversal) {
            $results[] = $this->denied(
                sprintf('Download server denies a path traversal (%s%s)', $basePath, str_replace($name, '<check>', $traversal)),
                $probe->fetch($origin.$basePath.$traversal.$name.'.txt?'.$query),
                $forbidden
            );
        }

        $results[] = $this->denied('Download server keeps the token validator internal', $probe->fetch($origin.'/_validate_token?'.$query), $forbidden);
        $results[] = $this->denied('Download server denies an Opencast video without a token', $probe->fetch($origin.'/opencast/'.$name.'/canary.mp4'), $forbidden);
        $results[] = $this->foreignOrigin($probe->fetch($signed, ['Origin: '.self::FOREIGN_ORIGIN]));
        $results[] = $this->denied('Frontend denies the storage directory', $probe->fetch($frontendUrl.'/storage/downloads/'.$name.'/canary.mp4'), $forbidden);
        $results[] = $this->denied('Frontend denies the storage directory with a valid token', $probe->fetch($frontendUrl.'/storage/downloads/'.$name.'/canary.mp4?'.$query), $forbidden);
        $results[] = $this->denied('Frontend denies the files outside the downloads', $probe->fetch($frontendUrl.'/storage/'.$name.'.txt'), $forbidden);

        return $results;
    }

    /**
     * @param array{token: string, expires: int} $token
     */
    private function hlsUrl(string $origin, array $token, string $directory, string $relative): string
    {
        return sprintf('%s/hls/%d/%s%s%s', $origin, $token['expires'], $token['token'], $directory, $relative);
    }

    private function query(SecureTokenService $secureTokenService, string $resource, ?int $expires = null): string
    {
        $token = $secureTokenService->generateToken($resource, $expires);

        return http_build_query(['token' => $token['token'], 'expires' => $token['expires'], 'resource' => $resource]);
    }

    private function served(string $name, DeliveryResponse $response, int $status, string $body): DeliveryCheckResult
    {
        if ($response->status !== $status) {
            return new DeliveryCheckResult($name, false, sprintf('expected HTTP %d, got %s', $status, $this->describe($response)));
        }

        if ($response->body !== $body) {
            return new DeliveryCheckResult($name, false, sprintf('HTTP %d with another content', $response->status));
        }

        return new DeliveryCheckResult($name, true, sprintf('HTTP %d', $response->status));
    }

    private function forcesDownload(DeliveryResponse $response): DeliveryCheckResult
    {
        $name = 'Download server forces the download';
        if (200 !== $response->status) {
            return new DeliveryCheckResult($name, false, sprintf('expected HTTP 200, got %s', $this->describe($response)));
        }

        $disposition = (string) $response->header('Content-Disposition');
        if (!str_contains(strtolower($disposition), 'attachment')) {
            return new DeliveryCheckResult($name, false, 'no attachment Content-Disposition');
        }

        return new DeliveryCheckResult($name, true, $disposition);
    }

    private function noindex(string $name, DeliveryResponse $response): DeliveryCheckResult
    {
        if (200 !== $response->status) {
            return new DeliveryCheckResult($name, false, sprintf('expected HTTP 200, got %s', $this->describe($response)));
        }

        $robots = (string) $response->header('X-Robots-Tag');
        if (!str_contains(strtolower($robots), 'noindex')) {
            return new DeliveryCheckResult($name, false, '' === $robots ? 'no X-Robots-Tag' : sprintf('X-Robots-Tag: %s', $robots));
        }

        return new DeliveryCheckResult($name, true, $robots);
    }

    private function foreignOrigin(DeliveryResponse $response): DeliveryCheckResult
    {
        $name = 'Download server denies CORS to another origin';
        if (200 !== $response->status) {
            return new DeliveryCheckResult($name, false, sprintf('expected HTTP 200, got %s', $this->describe($response)));
        }

        $allowed = (string) $response->header('Access-Control-Allow-Origin');
        if ('' !== $allowed) {
            return new DeliveryCheckResult($name, false, sprintf('Access-Control-Allow-Origin: %s', $allowed));
        }

        return new DeliveryCheckResult($name, true, 'no Access-Control-Allow-Origin');
    }

    /**
     * @param list<string> $forbidden
     */
    private function denied(string $name, DeliveryResponse $response, array $forbidden): DeliveryCheckResult
    {
        foreach ($forbidden as $content) {
            if (str_contains($response->body, $content)) {
                return new DeliveryCheckResult($name, false, sprintf('HTTP %d with the file', $response->status));
            }
        }

        if ($response->status >= 400 && $response->status < 500) {
            return new DeliveryCheckResult($name, true, sprintf('HTTP %d', $response->status));
        }

        return new DeliveryCheckResult($name, false, sprintf('expected HTTP 4xx, got %s', $this->describe($response)));
    }

    private function describe(DeliveryResponse $response): string
    {
        if (0 === $response->status) {
            return sprintf('no response (%s)', $response->error);
        }

        return sprintf('HTTP %d', $response->status);
    }

    private function write(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create the directory "%s".', $directory));
        }

        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException(sprintf('Unable to write the file "%s".', $path));
        }
    }
}
