<?php

namespace Pumukit\PodcastBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
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
        $activateFilter = $this->activateFilter($event);

        if ($activateFilter) {
            $this->settingParametersFilter();
        }
    }

    private function activateFilter($event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');

        $isMasterRequest = HttpKernelInterface::MASTER_REQUEST === $event->getRequestType();
        $isPodCastBundle = false !== strpos($req->attributes->get('_controller'), 'PodcastBundle');
        $isDefinedFilter = !isset($routeParams['filter']) || $routeParams['filter'];

        return $isMasterRequest && $isPodCastBundle && $isDefinedFilter;
    }

    private function settingParametersFilter()
    {
        $filter = $this->dm->getFilterCollection()->enable('frontend');

        $filter->setParameter('pub_channel_tag', 'PUCHPODCAST');
        $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
        $filter->setParameter('display_track_tag', 'podcast');
    }
}
