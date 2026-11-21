<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Pumukit\BasePlayerBundle\Controller\DownloadAuthController;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversNothing]
final class DownloadAuthControllerTest extends TestCase
{
    private const URL = 'https://dls.example.org/pumukit/series/my%20video.mp4';
    private const URI = '/pumukit/series/my%20video.mp4';

    private SecureTokenService $tokens;
    private DownloadAuthController $controller;

    protected function setUp(): void
    {
        $this->tokens = new SecureTokenService('secret');
        $this->controller = new DownloadAuthController($this->tokens, new NullLogger());
    }

    public function testTheSignedFileIsAllowed(): void
    {
        self::assertSame(200, $this->statusOf($this->tokens->generateDownloadUrl(self::URL), self::URI));
    }

    public function testTheTokenOfAFileDoesNotOpenAnotherFile(): void
    {
        self::assertSame(401, $this->statusOf($this->tokens->generateDownloadUrl(self::URL), '/pumukit/series/private.mp4'));
    }

    public function testAResourceCannotBeChangedToMatchAnotherFile(): void
    {
        $url = str_replace(urlencode('/pumukit/series/my video.mp4'), urlencode('/pumukit/series/private.mp4'), $this->tokens->generateDownloadUrl(self::URL));

        self::assertSame(401, $this->statusOf($url, '/pumukit/series/private.mp4'));
    }

    public function testATokenOfAPlayerLinkIsNotAccepted(): void
    {
        self::assertSame(401, $this->statusOf($this->tokens->generateSecureUrl(self::URL, '5f0000000000000000000000'), self::URI));
    }

    public function testAnExpiredTokenIsNotAccepted(): void
    {
        $url = (new SecureTokenService('secret', -10))->generateDownloadUrl(self::URL);

        self::assertSame(401, $this->statusOf($url, self::URI));
    }

    public function testATokenOfAnotherSecretIsNotAccepted(): void
    {
        $url = (new SecureTokenService('other'))->generateDownloadUrl(self::URL);

        self::assertSame(401, $this->statusOf($url, self::URI));
    }

    public function testARequestWithoutTheOriginalUriIsNotAccepted(): void
    {
        self::assertSame(401, $this->statusOf($this->tokens->generateDownloadUrl(self::URL), null));
    }

    public function testARequestWithoutTokenIsNotAccepted(): void
    {
        self::assertSame(401, $this->statusOf(self::URL, self::URI));
    }

    public function testValidateHlsAcceptsAFileOfTheSignedDirectory(): void
    {
        $url = $this->tokens->generateHlsUrl('https://dls.example.org', '/files/downloads/abc/def/', 'stream_0/data00.ts');

        self::assertSame(200, $this->hlsStatusOf(rawurldecode((string) parse_url($url, PHP_URL_PATH))));
    }

    public function testValidateHlsRejectsAnotherDirectory(): void
    {
        $url = $this->tokens->generateHlsUrl('https://dls.example.org', '/files/downloads/abc/def/', 'data.ts');
        $path = str_replace('/files/downloads/abc/def/', '/files/downloads/abc/other/', rawurldecode((string) parse_url($url, PHP_URL_PATH)));

        self::assertSame(401, $this->hlsStatusOf($path));
    }

    public function testValidateHlsRejectsAnMp4(): void
    {
        $url = $this->tokens->generateHlsUrl('https://dls.example.org', '/files/downloads/abc/def/', 'canary.mp4');

        self::assertSame(401, $this->hlsStatusOf(rawurldecode((string) parse_url($url, PHP_URL_PATH))));
    }

    public function testValidateHlsRejectsAnEncodedTraversal(): void
    {
        $url = $this->tokens->generateHlsUrl('https://dls.example.org', '/files/downloads/abc/def/', 'x.ts');

        self::assertSame(401, $this->hlsStatusOf(str_replace('/def/x.ts', '/def/%2e%2e/x.ts', (string) parse_url($url, PHP_URL_PATH))));
    }

    private function hlsStatusOf(string $originalUri): int
    {
        $request = Request::create('/download/validate-hls', 'GET');
        $request->headers->set('X-Original-URI', $originalUri);

        return $this->controller->validateHls($request)->getStatusCode();
    }

    private function statusOf(string $url, ?string $originalUri): int
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $request = Request::create('/download/validate-token', 'GET', $query);
        if (null !== $originalUri) {
            $request->headers->set('X-Original-URI', $originalUri);
        }

        return $this->controller->validateToken($request)->getStatusCode();
    }
}
