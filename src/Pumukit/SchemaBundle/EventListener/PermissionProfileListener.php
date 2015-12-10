<?php

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Event\PermissionProfileEvent;

class PermissionProfileEvent
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
    }

    public function postUpdate(PermissionProfileEvent $event)
    {
        $permissionProfile = $event->getPermissionProfile();

        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $userRepo = $dm->getRepository('PumukitSchemaBundle:User');

        // call user service
    }
}