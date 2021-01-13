<?php

declare(strict_types=1);

namespace Pumukit\UserBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CreateUserService
{
    private $userPasswordEncoder;
    private $permissionProfileService;
    private $personService;
    private $dispatcher;
    private $userRepository;

    public const SUPER_ADMIN_USER_ROLES = ['ROLE_SUPER_ADMIN'];

    public function __construct(
        DocumentManager $objectManager,
        UserPasswordEncoderInterface $userPasswordEncoder,
        PermissionProfileService $permissionProfileService,
        PersonService $personService,
        UserEventDispatcherService $dispatcher
    ) {
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->permissionProfileService = $permissionProfileService;
        $this->personService = $personService;
        $this->dispatcher = $dispatcher;

        $this->userRepository = $objectManager->getRepository(User::class);
    }

    public function createUser(string $username, string $password, string $email, string $fullName, PermissionProfile $permissionProfile): UserInterface
    {
        if($this->userExists($username)) {
            throw new \Exception('Username already on database');
        }

        return $this->create($username, $password, $email, $fullName, $permissionProfile);
    }

    public function createSuperAdmin(string $username, string $password, string $email): UserInterface
    {
        if($this->userExists($username)) {
            throw new \Exception('Username already on database');
        }

        return $this->create($username, $password, $email, null, null);
    }

    private function create(string $username, string $password, string $email, ?string $fullName, ?PermissionProfile $permissionProfile): UserInterface
    {
        $user = new User();
        $user->setUsername($username);
        $user->setFullName($fullName ?? $username);
        $user->setEmail($email);
        $user->setPassword($this->userPasswordEncoder->encodePassword($user, $password));
        $user->setEnabled(true);
        if($permissionProfile instanceof PermissionProfile) {
            $user->setPermissionProfile($permissionProfile);
        }

        $this->updateUserRolesAndPermission($user);
        $this->userRepository->save($user);

        $this->personService->referencePersonIntoUser($user);

        $this->dispatcher->dispatchCreate($user);

        return $user;
    }

    private function userExists(string $username)
    {
        return $this->userRepository->userExists($username);
    }

    private function updateUserRolesAndPermission(User $user): void
    {
        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null === $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }

        if (null !== ($permissionProfile = $user->getPermissionProfile())) {
            $this->setUserScope($user, '', $permissionProfile->getScope());
            $this->addRoles($user, $permissionProfile->getPermissions());
        } else {
            $this->addRoles($user, self::SUPER_ADMIN_USER_ROLES);
        }
    }

    private function setUserScope(User $user, string $oldScope = '', string $newScope = ''): void
    {
        if ($user->hasRole($oldScope)) {
            $user->removeRole($oldScope);
        }

        $this->addUserScope($user, $newScope);
    }

    private function addRoles(User $user, array $permissions = []): void
    {
        foreach ($permissions as $permission) {
            if (!$user->hasRole($permission)) {
                $user->addRole($permission);
            }
        }
    }

    private function addUserScope(User $user, string $scope = ''): void
    {
        if (array_key_exists($scope, PermissionProfile::$scopeDescription) && !$user->hasRole($scope)) {
            $user->addRole($scope);
        }
    }
}
