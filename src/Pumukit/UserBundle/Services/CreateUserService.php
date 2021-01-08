<?php

declare(strict_types=1);

namespace Pumukit\UserBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CreateUserService
{
    private $objectManager;
    private $userPasswordEncoder;
    private $permissionProfileService;
    private $userService;

    public const BASE_USER_ROLES = ['ROLE_USER'];
    public const SUPER_ADMIN_USER_ROLES = ['ROLE_SUPER_ADMIN'];

    public function __construct(
        DocumentManager $objectManager,
        UserPasswordEncoderInterface $userPasswordEncoder,
        PermissionProfileService $permissionProfileService,
        UserService $userService
    ) {
        $this->objectManager = $objectManager;
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->permissionProfileService = $permissionProfileService;
        $this->userService = $userService;
    }

    public function create(): User
    {
        return new User();
    }

    public function autocomplete(string $username, string $password, string $email, bool $isSuperAdmin): bool
    {
        if(!$this->validateUser($username)) {
            return true;
        }

        $user = $this->create();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($this->userPasswordEncoder->encodePassword($user, $password));
        $user->setEnabled(true);
        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null === $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }

        $this->setUserRoles($user, $isSuperAdmin);
        $this->userService->create($user);

        $this->objectManager->persist($user);
        $this->objectManager->flush();

        return true;
    }

    private function setUserRoles(UserInterface $user, bool $isSuperAdmin): void
    {
        $roles = $isSuperAdmin ? self::SUPER_ADMIN_USER_ROLES : self::BASE_USER_ROLES;

        $user->setRoles($roles);
    }

    private function validateUser(string $username): bool
    {
        $userExists = $this->objectManager->getRepository(User::class)->userExists($username);

        if(!$userExists) {
            return true;
        }

        return false;
    }
}
