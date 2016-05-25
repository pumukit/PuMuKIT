<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\NewAdminBundle\Controller\NewAdminController;

class NakedBackofficeListener
{
    public function __construct($pmk2_info)
    {
        $this->pmk2_info = $pmk2_info;
    }
    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();
        if (isset($this->pmk2_info['nakedBackofficeSubdomain'])
            && $req->getHttpHost() == $this->pmk2_info['nakedBackofficeSubdomain']) {
            $req->attributes->set('nakedbackoffice', true);
            if(isset($this->pmk2_info['nakedBackofficeColor']))
                $req->attributes->set('nakedbackoffice_color', $this->pmk2_info['nakedBackofficeColor']);
        }

        $routeParams = $req->attributes->get("_route_params");
        $isFilterActivated = (!isset($routeParams["filter"]) || $routeParams["filter"]);
    }
}
