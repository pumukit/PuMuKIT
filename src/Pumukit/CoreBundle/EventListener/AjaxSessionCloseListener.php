<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * See: https://tideways.io/profiler/blog/slow-ajax-requests-in-your-symfony-application-apply-this-simple-fix.
 */
class AjaxSessionCloseListener
{
    public function onKernelRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$request->isXmlHttpRequest()) {
            return;
        }

        if (!$request->attributes->has('_route')) {
            return;
        }

        /** @var SessionInterface */
        $session = $request->getSession();
        $session->save();
    }
}
