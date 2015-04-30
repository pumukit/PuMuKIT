<?php

namespace Pumukit\WebTVBundle\Listener;

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
        && "Pumukit\WebTVBundle" === substr($req->attributes->get("_controller"), 0, 19)
        && (!isset($routeParams["filter"]) || $routeParams["filter"])) {
      
      $filter = $this->dm->getFilterCollection()->enable("frontend");
      $filter->setParameter("pub_channel_tag", "PUCHWEBTV");
    }
  }
}