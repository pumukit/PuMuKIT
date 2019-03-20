<?php

namespace Pumukit\TemplateBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\EventListener\LocaleListener;

class RouteFoundHttpListener
{
    private $repository;
    private $httpKernel;
    private $requestStack;
    private $requestFixer;

    public function __construct(DocumentManager $dm,
                                HttpKernelInterface $httpKernel,
                                RequestStack $requestStack,
                                LocaleListener $requestFixer)
    {
        $this->repository = $dm->getRepository('PumukitTemplateBundle:Template');
        $this->httpKernel = $httpKernel;
        $this->requestStack = $requestStack;
        $this->requestFixer = $requestFixer;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof NotFoundHttpException) {
            $request = $event->getRequest();
            $this->requestFixer->fixRequestLocale($request);

            $requestUri = $request->getRequestUri();
            $pathInfo = $request->getPathInfo();
            $name = substr($pathInfo, 1);

            $t = $this->repository->findOneBy(array('name' => $name, 'hide' => false));
            if ($t) {
                $response = $this->forward('PumukitTemplateBundle:List:index', array('name' => $name));
                $response->headers->set('X-Status-Code', 200);
                $event->setResponse($response);
            }
        }
    }

    private function forward($controller, array $path = array(), array $query = array())
    {
        $path['_controller'] = $controller;
        $subRequest = $this->requestStack->getCurrentRequest()->duplicate($query, null, $path);

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
