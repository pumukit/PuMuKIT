<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\UserService;

class PermissionProfileListener
{
    private $userService;
    private $dm;
    private $logger;

    public function __construct(DocumentManager $dm, UserService $userService, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function postUpdate(PermissionProfileEvent $event)
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
