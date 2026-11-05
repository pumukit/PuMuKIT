<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Pumukit\CoreBundle\EventListener\FrameAncestorsListener;
use Pumukit\CoreBundle\Services\FrameAncestorsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
#[CoversNothing]
class FrameAncestorsListenerTest extends TestCase
{
    public array $logged = [];

    #[DataProvider('backoffice')]
    public function testTheBackofficeIsRestricted(string $path): void
    {
        $response = $this->dispatch($path, '');

        $this->assertSame("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public static function backoffice(): iterable
    {
        yield 'root' => ['/admin'];

        yield 'page' => ['/admin/series'];
    }

    #[DataProvider('others')]
    public function testOtherRoutesKeepTheirHeaders(string $path): void
    {
        $response = $this->dispatch($path, '');

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }

    public static function others(): iterable
    {
        yield 'file' => ['/trackfile/6aec4a3216d283d26007dd75.mp4'];

        yield 'player' => ['/iframe/6aec4a3216d283d26007dd75'];

        yield 'similar path' => ['/administration'];

        yield 'lms' => ['/openedx/sso/manager'];
    }

    public function testAllowedDomainsDoNotSendTheOldHeader(): void
    {
        $response = $this->dispatch('/admin/series', 'moodle.uni.es');

        $this->assertSame("frame-ancestors 'self' moodle.uni.es", $response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }

    public function testAnotherPolicyIsKept(): void
    {
        $response = new Response();
        $response->headers->set('Content-Security-Policy', "default-src 'self'");

        $this->dispatch('/admin/series', '', [], $response);

        $this->assertCount(2, $response->headers->all('Content-Security-Policy'));
    }

    #[DataProvider('blocked')]
    public function testABlockedIframeIsLogged(array $server, string $origin): void
    {
        $this->dispatch('/admin/series', 'moodle.uni.es', $server);

        $this->assertCount(1, $this->logged);
        $this->assertSame($origin, $this->logged[0]['origin']);
    }

    public static function blocked(): iterable
    {
        yield 'other site' => [['HTTP_SEC_FETCH_DEST' => 'iframe', 'HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_REFERER' => 'https://intranet.uni.es/page'], 'intranet.uni.es'];

        yield 'no referer' => [['HTTP_SEC_FETCH_DEST' => 'iframe', 'HTTP_SEC_FETCH_SITE' => 'cross-site'], 'unknown'];
    }

    #[DataProvider('notBlocked')]
    public function testOtherRequestsAreNotLogged(array $server, string $extra = 'moodle.uni.es'): void
    {
        $this->dispatch('/admin/series', $extra, $server);

        $this->assertSame([], $this->logged);
    }

    public static function notBlocked(): iterable
    {
        yield 'not an iframe' => [['HTTP_SEC_FETCH_DEST' => 'document', 'HTTP_REFERER' => 'https://evil.example.org/']];

        yield 'same origin' => [['HTTP_SEC_FETCH_DEST' => 'iframe', 'HTTP_SEC_FETCH_SITE' => 'same-origin']];

        yield 'allowed' => [['HTTP_SEC_FETCH_DEST' => 'iframe', 'HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_REFERER' => 'https://moodle.uni.es/mod']];

        yield 'any host' => [['HTTP_SEC_FETCH_DEST' => 'iframe', 'HTTP_SEC_FETCH_SITE' => 'cross-site'], '*'];
    }

    private function dispatch(string $path, string $extra, array $server = [], ?Response $response = null): Response
    {
        $test = $this;
        $logger = new class($test) extends AbstractLogger {
            public function __construct(private readonly FrameAncestorsListenerTest $test) {}

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->test->logged[] = $context;
            }
        };

        $response ??= new Response();
        $listener = new FrameAncestorsListener(new FrameAncestorsService([], $extra), $logger);
        $listener->onKernelResponse(new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('https://pumukit.example.org'.$path, 'GET', [], [], [], $server),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        return $response;
    }
}
