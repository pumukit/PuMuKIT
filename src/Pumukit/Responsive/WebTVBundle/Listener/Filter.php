<?php

namespace Pumukit\WebTVBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Pumukit\SchemaBundle\Document\Broadcast;

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
        && (false !== strpos($req->attributes->get("_controller"), 'WebTVBundle'))
        && (!isset($routeParams["filter"]) || $routeParams["filter"])) {
      
      $filter = $this->dm->getFilterCollection()->enable("frontend");
      $filter->setParameter("pub_channel_tag", "PUCHWEBTV");
      $filter->setParameter("private_broadcast", $this->getBroadcastCriteria());
    }
  }

    private function getPrivateBroadcastIds()
    {
        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $privateBroadcastIds = $broadcastRepo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        if (null != $privateBroadcastIds) {
            return $privateBroadcastIds->toArray();
        }
        return array();
    }

    private function getBroadcastCriteria()
    {
        $privateBroadcastIds = $this->getPrivateBroadcastIds();
        if (null != $privateBroadcastIds) {
            return array('$nin' => $privateBroadcastIds);
        }
        return array();
    }
}