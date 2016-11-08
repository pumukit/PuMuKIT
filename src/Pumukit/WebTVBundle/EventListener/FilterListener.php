<?php

namespace Pumukit\WebTVBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\WebTVBundle\Controller\WebTVController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class FilterListener
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');
        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         * From Symfony Docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
         */
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        //@deprecated: PuMuKIT 2.2: This logic will be removed eventually. Please implement the interface WebTVBundleController to use the filter.
        $deprecatedCheck = false && (false !== strpos($req->attributes->get('_controller'), 'WebTVBundle'));

        if (($controller[0] instanceof WebTVController /*deprecated*/|| $deprecatedCheck/**/)
            && $event->isMasterRequest()
                && $isFilterActivated) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            if (isset($routeParams['show_hide']) && $routeParams['show_hide']) {
                $filter->setParameter('status', array('$in' => array(MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE)));
            } else {
                $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
            }
            if (!isset($routeParams['track']) || $routeParams['track']) {
                $filter->setParameter('display_track_tag', 'display');
            }
            if (!isset($routeParams['no_channels']) || !$routeParams['no_channels']) {
                $filter->setParameter('pub_channel_tag', 'PUCHWEBTV');
            }
        }
    }
}
