<?php

namespace Pumukit\SchemaBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;

class PermissionProfileListener
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

        $userService = $this->container->get('pumukitschema.user');
        $countUsers = $userService->countUsersWithPermissionProfile($permissionProfile);
        if (0 < $countUsers) {
            $usersWithPermissionProfile = $userService->getUsersWithPermissionProfile($permissionProfile);
            foreach ($usersWithPermissionProfile as $user) {
                $user = $userService->update($user, false);
            }
            $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
            $dm->flush();
        }
    }
}