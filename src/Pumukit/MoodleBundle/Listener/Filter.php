<?php

namespace Pumukit\MoodleBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Filter
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
            && (false !== strpos($req->attributes->get("_controller"), 'MoodleBundle'))
            && (!isset($routeParams["filter"]) || $routeParams["filter"])) {
      
          $filter = $this->dm->getFilterCollection()->enable("frontend");
          $filter->setParameter("pub_channel_tag", "PUCHWEBTV");
          $filter->setParameter("display_track_tag", new \MongoRegex('/\bdisplay\b/'));
          $filter->setParameter("hide_track", false);
        }
    }
}