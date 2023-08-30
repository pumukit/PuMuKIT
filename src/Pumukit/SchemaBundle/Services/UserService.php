<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserService
{
    private $dm;
    private $repo;
    private $mmobjRepo;
    private $personRepo;
    private $groupRepo;
    private $permissionService;
    private $personalScopeDeleteOwners;
    private $dispatcher;
    private $permissionProfileService;
    private $multimediaObjectEventDispatcherService;
    private $tokenStorage;
    private $sendEmailWhenAddUserOwner;

    public function __construct(
        DocumentManager $documentManager,
        UserEventDispatcherService $dispatcher,
        PermissionService $permissionService,
        PermissionProfileService $permissionProfileService,
        MultimediaObjectEventDispatcherService $multimediaObjectEventDispatcherService,
        TokenStorageInterface $tokenStorage,
        $personalScopeDeleteOwners = false,
        $sendEmailWhenAddUserOwner = false
    ) {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(User::class);
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->personRepo = $this->dm->getRepository(Person::class);
        $this->groupRepo = $this->dm->getRepository(Group::class);
        $this->permissionService = $permissionService;
        $this->dispatcher = $dispatcher;
        $this->multimediaObjectEventDispatcherService = $multimediaObjectEventDispatcherService;
        $this->tokenStorage = $tokenStorage;
        $this->personalScopeDeleteOwners = $personalScopeDeleteOwners;
        $this->permissionProfileService = $permissionProfileService;
        $this->sendEmailWhenAddUserOwner = $sendEmailWhenAddUserOwner;
    }

    public function addOwnerUserToMultimediaObject(MultimediaObject $multimediaObject, User $user, bool $executeFlush = true)
    {
        $multimediaObject = $this->addOwnerUserToObject($multimediaObject, $user, $executeFlush);
        $this->addOwnerUserToObject($multimediaObject->getSeries(), $user, $executeFlush);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $multimediaObject;
    }

    public function removeOwnerUserFromMultimediaObject(MultimediaObject $multimediaObject, User $user, bool $executeFlush = true)
    {
        $multimediaObject = $this->removeOwnerUserFromObject($multimediaObject, $user, $executeFlush);
        $this->removeOwnerUserFromObject($multimediaObject->getSeries(), $user, $executeFlush);

        return $multimediaObject;
    }

    public function create(User $user)
    {
        if (null !== ($permissionProfile = $user->getPermissionProfile())) {
            $user = $this->setUserScope($user, null, $permissionProfile->getScope());
            $user = $this->addRoles($user, $permissionProfile->getPermissions(), false);
        }
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($user);

        return $user;
    }

    public function update(User $user, $executeFlush = true, $checkOrigin = true, $execute_dispatch = true)
    {
        if ($checkOrigin && !$user->isLocal()) {
            throw new \Exception('The user "'.$user->getUsername().'" is not local and can not be modified.');
        }
        if (!$user->isSuperAdmin()) {
            $permissionProfile = $user->getPermissionProfile();
            if (null === $permissionProfile) {
                throw new \Exception('The User "'.$user->getUsername().'" has no Permission Profile assigned.');
            }

            /** NOTE: User roles have:
             * - permission profile scope.
             */
            $userScope = $this->getUserScope($user->getRoles());
            if ($userScope !== $permissionProfile->getScope()) {
                $user = $this->setUserScope($user, $userScope, $permissionProfile->getScope());
            }
            $userPermissions = $this->getUserPermissions($user->getRoles());
            if ($userPermissions !== $permissionProfile->getPermissions()) {
                $user = $this->removeRoles($user, $userPermissions, false);
                $user = $this->addRoles($user, $permissionProfile->getPermissions(), false);
            }
        }
        $this->dm->persist($user);
        if ($executeFlush) {
            $this->dm->flush();
        }

        if ($execute_dispatch) {
            $this->dispatcher->dispatchUpdate($user);
        }

        return $user;
    }

    public function delete(User $user, bool $executeFlush = true)
    {
        $this->dm->remove($user);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchDelete($user);
    }

    public function addRoles(User $user, array $permissions = [], bool $executeFlush = true)
    {
        foreach ($permissions as $permission) {
            if (!$user->hasRole($permission)) {
                $user->addRole($permission);
            }
        }
        $this->dm->persist($user);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $user;
    }

    public function removeRoles(User $user, array $permissions = [], bool $executeFlush = true)
    {
        foreach ($permissions as $permission) {
            if ($user->hasRole($permission) && array_key_exists(
                $permission,
                $this->permissionService->getAllPermissions()
            )) {
                $user->removeRole($permission);
            }
        }
        $this->dm->persist($user);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $user;
    }

    public function countUsersWithPermissionProfile(PermissionProfile $permissionProfile)
    {
        return $this->repo->createQueryBuilder()
            ->field('permissionProfile')->references($permissionProfile)
            ->count()
            ->getQuery()
            ->execute()
        ;
    }

    public function getUsersWithPermissionProfile(PermissionProfile $permissionProfile)
    {
        return $this->repo->createQueryBuilder()
            ->field('permissionProfile')->references($permissionProfile)
            ->getQuery()
            ->execute()
        ;
    }

    public function getUserPermissions(array $userRoles = [])
    {
        $userPermissions = [];
        foreach ($userRoles as $userRole) {
            if (array_key_exists($userRole, $this->permissionService->getAllPermissions())) {
                $userPermissions[] = $userRole;
            }
        }

        return $userPermissions;
    }

    public function setUserScope(User $user, ?string $oldScope = '', string $newScope = '')
    {
        if ($oldScope && $user->hasRole($oldScope)) {
            $user->removeRole($oldScope);
        }

        return $this->addUserScope($user, $newScope);
    }

    public function getUserScope(array $userRoles = [])
    {
        foreach ($userRoles as $userRole) {
            if (array_key_exists($userRole, PermissionProfile::$scopeDescription)) {
                return $userRole;
            }
        }

        return null;
    }

    public function addUserScope(User $user, string $scope = '')
    {
        if (array_key_exists($scope, PermissionProfile::$scopeDescription) && !$user->hasRole($scope)) {
            $user->addRole($scope);
            $this->dm->persist($user);
            $this->dm->flush();
        }

        return $user;
    }

    public function hasGlobalScope(User $user)
    {
        if ($user->getPermissionProfile()) {
            return $user->getPermissionProfile()->isGlobal();
        }

        return false;
    }

    public function hasPersonalScope(User $user)
    {
        if ($user->getPermissionProfile()) {
            return $user->getPermissionProfile()->isPersonal();
        }

        return false;
    }

    public function hasNoneScope(User $user)
    {
        if ($user->getPermissionProfile()) {
            return $user->getPermissionProfile()->isNone();
        }

        return false;
    }

    public function addGroup(Group $group, User $user, bool $executeFlush = true, bool $checkOrigin = true)
    {
        if (!$user->containsGroup($group)) {
            if ($checkOrigin) {
                if (!$this->isAllowedToModifyUserGroup($user, $group)) {
                    throw new \Exception('Not allowed to add group "'.$group->getKey().'" to user "'.$user->getUsername().'".');
                }
            }
            $user->addGroup($group);
            $this->dm->persist($user);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($user);
        }
    }

    public function deleteGroup(Group $group, User $user, bool $executeFlush = true, bool $checkOrigin = true)
    {
        if ($user->containsGroup($group)) {
            if ($checkOrigin) {
                if (!$this->isAllowedToModifyUserGroup($user, $group)) {
                    throw new \Exception('Not allowed to delete group "'.$group->getKey().'" from user "'.$user->getUsername().'".');
                }
            }
            $user->removeGroup($group);
            $this->dm->persist($user);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($user);
        }
    }

    public function isAllowedToModifyUserGroup(User $user, Group $group)
    {
        return !(!$user->isLocal() && !$group->isLocal());
    }

    public function findWithGroup(Group $group)
    {
        return $this->repo->createQueryBuilder()
            ->field('groups')->in([new ObjectId($group->getId())])
            ->getQuery()
            ->execute()
        ;
    }

    public function deleteAllFromGroup(Group $group)
    {
        $users = $this->findWithGroup($group);
        foreach ($users as $user) {
            $this->deleteGroup($group, $user, false);
        }
        $this->dm->flush();
    }

    public function isUserLastRelation(User $loggedInUser, ?string $mmId = null, ?string $personId = null, array $owners = [], array $addGroups = [])
    {
        $personToRemoveIsLogged = $this->isLoggedPersonToRemoveFromOwner($loggedInUser, $personId);
        $userInOwners = $this->isUserInOwners($loggedInUser, $owners);
        $userInAddGroups = $this->isUserInGroups($loggedInUser, $mmId, $personId, $addGroups);

        // Show warning??
        if (($personToRemoveIsLogged && !$userInAddGroups)
            || (!$personToRemoveIsLogged && !$userInOwners && !$userInAddGroups)
            || (!$userInOwners && !$userInAddGroups)) {
            return true;
        }

        return false;
    }

    public function isLoggedPersonToRemoveFromOwner(User $loggedInUser, string $personId)
    {
        $personToRemove = $this->personRepo->find($personId);
        if ($personToRemove) {
            $userToRemove = $personToRemove->getUser();
            if (!$userToRemove) {
                return false;
            }
            if ($this->hasGlobalScope($userToRemove)) {
                return false;
            }
            if ($userToRemove->hasRole('ROLE_SUPER_ADMIN')) {
                return false;
            }
            if ($loggedInUser === $userToRemove) {
                return true;
            }
        }

        return false;
    }

    public function isUserInOwners(User $loggedInUser, array $owners = [])
    {
        $userInOwners = false;
        foreach ($owners as $owner) {
            $ownerArray = explode('_', $owner);
            $personId = end($ownerArray);
            $person = $this->personRepo->find($personId);
            if ($person) {
                if ($loggedInUser === $person->getUser()) {
                    $userInOwners = true;

                    break;
                }
            }
        }

        return $userInOwners;
    }

    public function isUserInGroups(User $loggedInUser, ?string $mmId = null, ?string $personId = null, array $groups = [])
    {
        $userInAddGroups = false;
        $userGroups = $loggedInUser->getGroups()->toArray();
        if ($personId && $mmId) {
            $multimediaObject = $this->mmobjRepo->find($mmId);
            if ($multimediaObject) {
                foreach ($multimediaObject->getGroups() as $mmGroup) {
                    if (in_array($mmGroup, $userGroups)) {
                        $userInAddGroups = true;

                        break;
                    }
                }
            }
        } else {
            foreach ($groups as $groupString) {
                $groupArray = explode('_', $groupString);
                $groupId = end($groupArray);
                $group = $this->groupRepo->find($groupId);
                if ($group) {
                    if (in_array($group, $userGroups)) {
                        $userInAddGroups = true;

                        break;
                    }
                }
            }
        }

        return $userInAddGroups;
    }

    public function updatePermissionProfile(PermissionProfile $permissionProfile, array $permissions, $checkOrigin = true)
    {
        $queryBuilder = $this->dm->createQueryBuilder(User::class);
        $queryBuilder->updateMany();
        $queryBuilder->field('permissionProfile')->equals(new ObjectId($permissionProfile->getId()));
        if ($checkOrigin) {
            $queryBuilder->field('origin')->equals('local');
        }

        // Permissions have SCOPE added on array.
        $queryBuilder->field('roles')->set($permissions);
        $queryBuilder->field('permissionProfile')->set($permissionProfile->getId());
        $queryBuilder->getQuery()->execute();
    }

    private function addOwnerUserToObject($object, User $user, $executeFlush = true)
    {
        if (null !== $object) {
            $owners = $object->getProperty('owners');
            if (null === $owners) {
                $owners = [];
            }
            if (!in_array($user->getId(), $owners, true)) {
                $owners[] = $user->getId();
                $object->setProperty('owners', $owners);
                $this->dm->persist($object);
                if ($this->sendEmailWhenAddUserOwner && $object instanceof MultimediaObject) {
                    $userLogged = $this->tokenStorage->getToken()->getUser();
                    if ($userLogged->getUsername() !== $user->getEmail() && !$object->isPrototype()) {
                        $this->multimediaObjectEventDispatcherService->dispatchMultimediaObjectAddOwner(
                            $object,
                            $userLogged,
                            $user
                        );
                    }
                }
            }
            if ($executeFlush) {
                $this->dm->flush();
            }
        }

        return $object;
    }

    private function removeOwnerUserFromObject($object, User $user, bool $executeFlush = true)
    {
        if (null !== $object) {
            $owners = $object->getProperty('owners');
            if (in_array($user->getId(), $owners, true)) {
                if ($object->isCollection()) {
                    // NOTE: Check all MultimediaObjects from the Series, even the prototype
                    $mmObjRepo = $this->dm->getRepository(MultimediaObject::class);
                    $multimediaObjects = $mmObjRepo->createQueryBuilder()
                        ->field('series')->equals($object)
                    ;
                    $deleteOwnerInSeries = true;
                    foreach ($multimediaObjects as $multimediaObject) {
                        if (null !== $owners = $multimediaObject->getProperty('owners')) {
                            if (in_array($user->getId(), $owners, true)) {
                                $deleteOwnerInSeries = false;
                            }
                        }
                    }
                    if ($deleteOwnerInSeries) {
                        $object = $this->removeUserFromOwnerProperty($object, $user, $executeFlush);
                    }
                } else {
                    $object = $this->removeUserFromOwnerProperty($object, $user, $executeFlush);
                }
            }
        }

        return $object;
    }

    private function removeUserFromOwnerProperty($object, User $user, bool $executeFlush = true)
    {
        if (null !== $object) {
            $owners = array_filter($object->getProperty('owners'), static function ($ownerId) use ($user) {
                return $ownerId !== $user->getId();
            });
            $object->setProperty('owners', $owners);

            $this->dm->persist($object);
            if ($executeFlush) {
                $this->dm->flush();
            }
        }

        return $object;
    }
}
