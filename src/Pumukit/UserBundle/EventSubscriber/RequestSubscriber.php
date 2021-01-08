<?php

declare(strict_types=1);

namespace Pumukit\UserBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class RequestSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest() || $request->isXmlHttpRequest() || 'pumukit_login' === $request->attributes->get('_route')) {
            return;
        }

        $this->saveTargetPath($this->session, 'main', $request->getUri());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }
}
