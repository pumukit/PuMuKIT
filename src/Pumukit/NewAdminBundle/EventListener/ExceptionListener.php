<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener
{
    /**
     * Handles security related exceptions.
     *
     * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event An GetResponseForExceptionEvent instance
     */
    public function onKernelException(\Symfony\Component\HttpKernel\Event\ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        do {
            if ($exception instanceof AccessDeniedException) {
                return $this->handleAccessDeniedException($event, $exception);
            }
        } while (null !== $exception = $exception->getPrevious());
    }

    private function handleAccessDeniedException(\Symfony\Component\HttpKernel\Event\ExceptionEvent $event, AccessDeniedException $exception)
    {
        $req = $event->getRequest();
        if ($req->isXmlHttpRequest()) {
            $exception = $event->getThrowable();

            $response = new Response();
            $response->setContent($exception->getMessage());
            $response->setStatusCode($exception->getCode());

            $event->setResponse($response);
        }
    }
}
