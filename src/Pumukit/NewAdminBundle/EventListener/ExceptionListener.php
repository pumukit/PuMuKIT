<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /**
     * Handles security related exceptions.
     *
     * @param GetResponseForExceptionEvent $event An GetResponseForExceptionEvent instance
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        do {
            if ($exception instanceof AccessDeniedException) {
                return $this->handleAccessDeniedException($event, $exception);
            }
        } while (null !== $exception = $exception->getPrevious());
    }


    private function handleAccessDeniedException(GetResponseForExceptionEvent $event, AccessDeniedException $exception)
    {
        $req = $event->getRequest();
        if ($req->isXmlHttpRequest()) {
            $exception = $event->getException();

            $response = new Response();
            $response->setContent($exception->getMessage());
            $response->setStatusCode($exception->getCode());

            $event->setResponse($response);
        }
    }
}
