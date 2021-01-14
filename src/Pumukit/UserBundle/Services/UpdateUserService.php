<?php

declare(strict_types=1);

namespace Pumukit\UserBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UpdateUserService extends CommonUserService
{
    private $dispatcher;
    private $userPasswordEncoder;
    private $userRepository;

    public function __construct(
        DocumentManager $objectManager,
        PermissionProfileService $permissionProfileService,
        UserPasswordEncoderInterface $userPasswordEncoder,
        UserEventDispatcherService $dispatcher
    ) {
        parent::__construct($objectManager, $permissionProfileService);
        $this->dispatcher = $dispatcher;
        $this->userPasswordEncoder = $userPasswordEncoder;
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
            $user->setPassword($this->userPasswordEncoder->encodePassword(
                $user,
                $user->getPlainPassword()
            ));

            $user->setPlainPassword(null);
        }
    }
}
