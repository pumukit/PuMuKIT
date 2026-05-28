<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UpdateUserService extends CommonUserService
{
    protected $userRepository;
    private $dispatcher;
    private $userPasswordHasher;

    public function __construct(
        DocumentManager $objectManager,
        PermissionProfileService $permissionProfileService,
        UserPasswordHasherInterface $userPasswordHasher,
        UserEventDispatcherService $dispatcher
    ) {
        parent::__construct($objectManager, $permissionProfileService);
        $this->dispatcher = $dispatcher;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userRepository = $objectManager->getRepository(User::class);
    }

    public function update(
        UserInterface $user,
        bool $executeFlush = true,
        bool $checkOrigin = true,
        bool $execute_dispatch = true
    ): UserInterface {
        if (!$this->isValidOrigin($user, $checkOrigin)) {
            throw new \Exception('The user "'.$user->getUsername().'" is not local and can not be modified.');
        }

        $this->updateUserRolesAndPermission($user);
        $this->updateUserPassword($user);

        $this->userRepository->persist($user);
        if ($executeFlush) {
            $this->userRepository->save($user);
        }

        if ($execute_dispatch) {
            $this->dispatcher->dispatchUpdate($user);
        }

        return $user;
    }

    private function updateUserPassword(UserInterface $user): void
    {
        if (null !== $user->getPlainPassword()) {
            $user->setPassword($this->userPasswordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            ));

            $user->setPlainPassword(null);
        }
    }
}
