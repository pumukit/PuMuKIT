<?php

namespace Pumukit\Legacy\WebTVBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
        $routeParams = $req->attributes->get('_route_params');

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()
        && (false !== strpos($req->attributes->get('_controller'), 'WebTVBundle'))
        && (!isset($routeParams['filter']) || $routeParams['filter'])) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            if (isset($routeParams['show_hide']) && $routeParams['show_hide']) {
                $filter->setParameter('status', array('$in' => array(MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE)));
            } else {
                $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
            }
            if (!isset($routeParams['broadcast']) || $routeParams['broadcast']) {
                $filter->setParameter('private_broadcast', $this->getBroadcastCriteria());
            }
            if (!isset($routeParams['track']) || $routeParams['track']) {
                $filter->setParameter('display_track_tag', 'display');
            }
            $filter->setParameter('pub_channel_tag', 'PUCHWEBTV');
        }
    }

    private function getPrivateBroadcastIds()
    {
        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $privateBroadcastIds = $broadcastRepo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        if (null !== $privateBroadcastIds) {
            return $privateBroadcastIds->toArray();
        }

        return array();
    }

    private function getBroadcastCriteria()
    {
        $privateBroadcastIds = $this->getPrivateBroadcastIds();
        if (null !== $privateBroadcastIds) {
            return array('$nin' => $privateBroadcastIds);
        }

        return array();
    }
}
