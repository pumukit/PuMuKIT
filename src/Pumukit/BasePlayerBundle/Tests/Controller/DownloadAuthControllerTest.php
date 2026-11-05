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
