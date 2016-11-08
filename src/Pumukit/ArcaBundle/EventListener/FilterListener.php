<?php

namespace Pumukit\ArcaBundle\EventListener;

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
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST &&
        "Pumukit\ArcaBundle" === substr($req->attributes->get('_controller'), 0, 18)) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            $filter->setParameter('pub_channel_tag', 'PUCHARCA');
        }
    }
}
