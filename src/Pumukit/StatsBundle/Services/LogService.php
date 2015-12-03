<?php

namespace Pumukit\StatsBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class LogService
{
    private $dm;
    private $requestStack;

    public function __construct(DocumentManager $documentManager, RequestStack $requestStack)
    {
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        
    }


    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $req = $this->requestStack->getMasterRequest();
        $log = new ViewsLog($req->getUri(),
                            $req->getClientIp(),
                            $req->headers->get('user-agent'),
                            $req->headers->get('referer'),
                            $event->getMultimediaObject()->getId(),
                            $event->getMultimediaObject()->getSeries()->getId(),
                            $event->getTrack() ? $event->getTrack()->getId() : null);

        $this->dm->persist($log);
        $this->dm->flush();        
    }
}
