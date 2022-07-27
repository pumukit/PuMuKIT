<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Pumukit\SchemaBundle\Services\UserService;

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
        $permissions = $permissionProfile->getPermissions();

        $permissions[] = $permissionProfile->getScope();

        try {
            $this->userService->updatePermissionProfile($permissionProfile, $permissions, false);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
