<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\UserBundle\Services\UpdateUserService;

class PermissionProfileListener
{
    private $objectManager;
    private $updateUserService;
    private $userService;

    public function __construct(
        DocumentManager $objectManager,
        UserService $userService,
        UpdateUserService $updateUserService
    ) {
        $this->objectManager = $objectManager;
        $this->userService = $userService;
        $this->updateUserService = $updateUserService;
    }

    public function postUpdate(PermissionProfileEvent $event): void
    {
        $permissionProfile = $event->getPermissionProfile();
        $countUsers = $this->userService->countUsersWithPermissionProfile($permissionProfile);
        if (0 < $countUsers) {
            try {
                $usersWithPermissionProfile = $this->userService->getUsersWithPermissionProfile($permissionProfile);
                foreach ($usersWithPermissionProfile as $user) {
                    $this->updateUserService->update($user, false, false, false);
                }
                $this->objectManager->flush();
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
        }
    }
}
