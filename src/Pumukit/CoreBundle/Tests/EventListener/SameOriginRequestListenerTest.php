<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\EventListener\SameOriginRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
#[CoversNothing]
class SameOriginRequestListenerTest extends TestCase
{
    #[DataProvider('rejected')]
    public function testCrossSiteRequestsThatChangeDataAreRejected(string $method, string $path, array $headers): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->dispatch($method, $path, $headers);
    }

    public static function rejected(): iterable
    {
        yield 'cross-site POST' => ['POST', '/admin/series/1/delete', ['HTTP_SEC_FETCH_SITE' => 'cross-site']];

        yield 'same-site POST from another subdomain' => ['POST', '/admin/series/1/delete', ['HTTP_SEC_FETCH_SITE' => 'same-site']];

        yield 'DELETE from another origin without fetch metadata' => ['DELETE', '/admin/series/1', ['HTTP_ORIGIN' => 'https://attacker.example']];

        yield 'POST from a sandboxed document without fetch metadata' => ['POST', '/admin/series/1/delete', ['HTTP_ORIGIN' => 'null']];

        yield 'chat message from another site' => ['POST', '/chat/post/1', ['HTTP_SEC_FETCH_SITE' => 'cross-site']];
    }

    #[DataProvider('accepted')]
    public function testOtherRequestsAreAccepted(string $method, string $path, array $headers): void
    {
        $event = $this->dispatch($method, $path, $headers);

        $this->assertNull($event->getResponse());
    }

    public static function accepted(): iterable
    {
        yield 'same-origin POST' => ['POST', '/admin/series/1/delete', ['HTTP_SEC_FETCH_SITE' => 'same-origin']];

        yield 'POST typed by the user' => ['POST', '/admin/series/1/delete', ['HTTP_SEC_FETCH_SITE' => 'none']];

        yield 'POST with the same origin and no fetch metadata' => ['POST', '/admin/series/1/delete', ['HTTP_ORIGIN' => 'https://pumukit.example']];

        yield 'POST without browser headers' => ['POST', '/admin/series/1/delete', []];

        yield 'cross-site GET' => ['GET', '/admin/series/', ['HTTP_SEC_FETCH_SITE' => 'cross-site']];

        yield 'cross-site LTI launch' => ['POST', '/lti/launch', ['HTTP_SEC_FETCH_SITE' => 'cross-site']];

        yield 'cross-site SSO response' => ['POST', '/saml/acs', ['HTTP_SEC_FETCH_SITE' => 'cross-site']];
    }

    private function dispatch(string $method, string $path, array $headers): RequestEvent
    {
        $request = Request::create('https://pumukit.example'.$path, $method, server: $headers);
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        (new SameOriginRequestListener())->onKernelRequest($event);

        return $event;
    }
}
