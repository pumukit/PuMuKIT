<?php

namespace Pumukit\TemplateBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

class RouteFoundHttpListener
{
    private $templatEngine;
    private $repository;

    public function __construct(DocumentManager $dm, EngineInterface $templatEngine)
    {
        $this->templatEngine = $templatEngine;
        $this->repository = $dm->getRepository('PumukitTemplateBundle:Template');
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof NotFoundHttpException) {
            $request = $event->getRequest();
            $requestUri = $request->getRequestUri();
            $pathInfo = $request->getPathInfo();
            $name = substr($pathInfo, 1);

            $t = $this->repository->findOneBy(array('name' => $name, 'hide' => false));
            if ($t) {
                $response = $this->templatEngine->renderResponse('PumukitTemplateBundle:List:index.html.twig', array('template' => $t));
                $event->setResponse($response);
            }
        }
    }
}
