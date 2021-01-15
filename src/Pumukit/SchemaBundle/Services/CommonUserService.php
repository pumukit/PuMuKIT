<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\User\UserInterface;

class CommonUserService
{
    public const SUPER_ADMIN_USER_ROLES = ['ROLE_SUPER_ADMIN'];
    private $userRepository;
    private $permissionProfileService;

    public function __construct(
        DocumentManager $documentManager,
        PermissionProfileService $permissionProfileService
    ) {
        $this->userRepository = $documentManager->getRepository(User::class);
        $this->permissionProfileService = $permissionProfileService;
    }

    protected function userExists(string $username)
    {
        return $this->userRepository->userExists($username);
    }

    protected function isValidOrigin(UserInterface $user, bool $checkOrigin = true): bool
    {
        if (!$checkOrigin) {
            return true;
        }

        return $user->isLocal();
    }

    protected function updateUserRolesAndPermission(UserInterface $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null === $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }

        if (null !== ($permissionProfile = $user->getPermissionProfile())) {
            $this->addUserRoles($user, $permissionProfile->getPermissions());
            $this->setUserScope($user, '', $permissionProfile->getScope());
        } else {
            $this->addUserRoles($user, self::SUPER_ADMIN_USER_ROLES);
        }
    }

    protected function setUserScope(UserInterface $user, string $oldScope = '', string $newScope = ''): void
    {
        if ($user->hasRole($oldScope)) {
            $user->removeRole($oldScope);
        }

        $this->addUserScope($user, $newScope);
    }

    protected function addUserScope(UserInterface $user, string $scope = ''): void
    {
        if (array_key_exists($scope, PermissionProfile::$scopeDescription) && !$user->hasRole($scope)) {
            $user->addRole($scope);
        }
    }

    protected function addUserRoles(UserInterface $user, array $permissions = []): void
    {
        $user->setRoles($permissions);
    }
}
