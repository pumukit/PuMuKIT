<?php

namespace Pumukit\TemplateBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\EventListener\LocaleListener;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;

class RouteFoundHttpListener
{
    private $templatEngine;
    private $repository;
    private $requestFixer;
    private $breadcrumbsService;

    public function __construct(DocumentManager $dm, EngineInterface $templatEngine,
                                LocaleListener $requestFixer, BreadcrumbsService $breadcrumbsService)
    {
        $this->templatEngine = $templatEngine;
        $this->repository = $dm->getRepository('PumukitTemplateBundle:Template');
        $this->requestFixer = $requestFixer;
        $this->breadcrumbsService = $breadcrumbsService;
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
                $this->breadcrumbsService->addList($t->getName(), 'pumukit_webtv_index_index', array(), true);
                $response = $this->templatEngine->renderResponse('PumukitTemplateBundle:List:index.html.twig', array('template' => $t));
                $event->setResponse($response);
            }
        }
    }
}
