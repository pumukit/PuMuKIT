<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\FrameAncestorsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class FrameAncestorsListener
{
    private const BACKOFFICE = '#^/admin(/|$)#';

    public function __construct(
        private readonly FrameAncestorsService $frameAncestors,
        private readonly LoggerInterface $frameAncestorsLogger,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || 1 !== preg_match(self::BACKOFFICE, $request->getPathInfo())) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('Content-Security-Policy', $this->frameAncestors->policy(), false);
        if ([] === $this->frameAncestors->sources()) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        $this->logBlockedIframe($request);
    }

    private function logBlockedIframe(Request $request): void
    {
        $destination = $request->headers->get('Sec-Fetch-Dest');
        if (('iframe' !== $destination && 'frame' !== $destination) || 'same-origin' === $request->headers->get('Sec-Fetch-Site')) {
            return;
        }

        $referer = (string) $request->headers->get('Referer');
        $host = parse_url($referer, PHP_URL_HOST);
        if (is_string($host) && (strtolower($host) === strtolower($request->getHost()) || $this->frameAncestors->allows($host))) {
            return;
        }
        if ($this->frameAncestors->allowsAnyHost()) {
            return;
        }

        $this->frameAncestorsLogger->warning('The browser will block a backoffice iframe from {origin}', [
            'origin' => is_string($host) ? $host : 'unknown',
            'path' => $request->getPathInfo(),
            'untranslatable_domains' => $this->frameAncestors->untranslatable(),
        ]);
    }
}
