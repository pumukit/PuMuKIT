<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SameOriginRequestListener
{
    private const PROTECTED_PATHS = '#^/(admin|chat)(/|$)#';
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest() || in_array($request->getMethod(), self::SAFE_METHODS, true) || !preg_match(self::PROTECTED_PATHS, $request->getPathInfo())) {
            return;
        }

        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if (null !== $fetchSite) {
            if (!in_array($fetchSite, ['same-origin', 'none'], true)) {
                throw new AccessDeniedHttpException('Cross-site requests cannot change data.');
            }

            return;
        }

        $origin = $request->headers->get('Origin');
        if (null === $origin) {
            return;
        }

        $port = parse_url($origin, PHP_URL_PORT);
        if (parse_url($origin, PHP_URL_HOST).($port ? ':'.$port : '') !== $request->getHttpHost()) {
            throw new AccessDeniedHttpException('Cross-site requests cannot change data.');
        }
    }
}
