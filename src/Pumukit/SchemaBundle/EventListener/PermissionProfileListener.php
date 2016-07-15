<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\UserService;

class PermissionProfileListener
{
    private $userService;
    private $dm;

    public function __construct(DocumentManager $dm, UserService $userService)
    {
        $this->dm = $dm;
        $this->userService = $userService;
    }

    public function postUpdate(PermissionProfileEvent $event)
    {
        $permissionProfile = $event->getPermissionProfile();
        $countUsers = $this->userService->countUsersWithPermissionProfile($permissionProfile);
        if (0 < $countUsers) {
            $usersWithPermissionProfile = $this->userService->getUsersWithPermissionProfile($permissionProfile);
            foreach ($usersWithPermissionProfile as $user) {
                $user = $this->userService->update($user, false, false);
            }
            $this->dm->flush();
        }
    }
}