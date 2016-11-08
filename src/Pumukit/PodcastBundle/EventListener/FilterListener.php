<?php

namespace Pumukit\PodcastBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FilterListener
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get("_route_params");

        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST
        && (false !== strpos($req->attributes->get("_controller"), 'PodcastBundle'))
        && (!isset($routeParams["filter"]) || $routeParams["filter"])) {
            $filter = $this->dm->getFilterCollection()->enable("frontend");
            $filter->setParameter("pub_channel_tag", "PUCHPODCAST");
        }
    }
}
