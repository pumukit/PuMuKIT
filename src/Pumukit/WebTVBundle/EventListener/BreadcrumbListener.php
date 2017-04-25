<?php

namespace Pumukit\WebTVBundle\EventListener;

use Pumukit\WebTVBundle\Controller\WebTVController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Pumukit\WebTVBundle\Services\BreadcrumbService;

class BreadcrumbListener
{
    private $breadcrumbService;
    private $activeBreadcrumb;

    public function __construct(BreadcrumbService $breadcrumbService, $activeBreadcrumb)
    {
        $this->breadcrumbService = $breadcrumbService;
        $this->activeBreadcrumb = $activeBreadcrumb;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->isMasterRequest()) {
            $controller = $event->getController();
            if ($controller[0] instanceof WebTVController) {
                $request = $event->getRequest();
                if ($this->activeBreadcrumb) {
                    $this->breadcrumbService->createBreadcrumb($request);
                }
                $request->attributes->set('active_breadcrumb', $this->activeBreadcrumb);
            }
        }
    }
}
