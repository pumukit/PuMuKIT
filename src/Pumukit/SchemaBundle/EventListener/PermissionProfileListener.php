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
        $countUsers = $this->userService->countUsersWithPermissionProfile($permissionProfile);
        if (0 < $countUsers) {
            try {
                $usersWithPermissionProfile = $this->userService->getUsersWithPermissionProfile($permissionProfile);
                foreach ($usersWithPermissionProfile as $user) {
                    $this->userService->update($user, false, false, false);
                }
                $this->dm->flush();
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
        }
    }
}
