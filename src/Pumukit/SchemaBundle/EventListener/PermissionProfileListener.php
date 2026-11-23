<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\UserService;

class PermissionProfileListener
{
    private $userService;

    public function __construct(
        UserService $userService
    ) {
        $this->userService = $userService;
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
