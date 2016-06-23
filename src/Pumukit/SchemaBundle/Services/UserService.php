<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Doctrine\ODM\MongoDB\DocumentManager;

class UserService
{
    private $dm;
    private $repo;
    private $personRepo;
    private $groupRepo;
    private $permissionService;
    private $personalScopeDeleteOwners;
    private $dispatcher;
    private $permissionProfileService;
    private $genUserSalt;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     * @param UserEventDispatcherService $dispatcher
     * @param PermissionService $permissionService
     * @param PermissionProfileService $permissionProfileService
     */
    public function __construct(DocumentManager $documentManager, UserEventDispatcherService $dispatcher, PermissionService $permissionService, PermissionProfileService $permissionProfileService, $personalScopeDeleteOwners=false, $genUserSalt=false)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:User');
        $this->personRepo = $this->dm->getRepository('PumukitSchemaBundle:Person');
        $this->groupRepo = $this->dm->getRepository('PumukitSchemaBundle:Group');
        $this->permissionService = $permissionService;
        $this->dispatcher = $dispatcher;
        $this->personalScopeDeleteOwners = $personalScopeDeleteOwners;
        $this->permissionProfileService = $permissionProfileService;
        $this->genUserSalt = $genUserSalt;
    }


    /**
     * Add owner user to MultimediaObject
     *
     * Add user id of the creator of the
     * Multimedia Object as property
     *
     * @param MultimediaObject $multimediaObject
     * @param User             $user
     * @param boolean          $executeFlush
     * @return MultimediaObject
     */
    public function addOwnerUserToMultimediaObject(MultimediaObject $multimediaObject, User $user, $executeFlush=true)
    {
        $multimediaObject = $this->addOwnerUserToObject($multimediaObject, $user, $executeFlush);
        $series = $this->addOwnerUserToObject($multimediaObject->getSeries(), $user, $executeFlush);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $multimediaObject;
    }

    /**
     * Add owner user to object
     *
     * Add user id of the creator of the
     * Multimedia Object or Series as property
     *
     * @param MultimediaObject|Series $object
     * @param User                    $user
     * @param boolean                 $executeFlush
     * @return MultimediaObject
     */
    private function addOwnerUserToObject($object, User $user, $executeFlush=true)
    {
        if (null != $object) {
            $owners = $object->getProperty('owners');
            if (null == $owners) {
                $owners = array();
            }
            if (!in_array($user->getId(), $owners)) {
                $owners[] = $user->getId();
                $object->setProperty('owners', $owners);
                $this->dm->persist($object);
            }
            if ($executeFlush) {
                $this->dm->flush();
            }
        }

        return $object;
    }

    /**
     * Remove owner user from MultimediaObject
     *
     * Remove user id of the
     * Multimedia Object as property if
     * is logged in user and not admin
     *
     * @param MultimediaObject $multimediaObject
     * @param User             $user
     * @param boolean          $executeFlush
     * @return MultimediaObject
     */
    public function removeOwnerUserFromMultimediaObject(MultimediaObject $multimediaObject, User $user, $executeFlush=true)
    {
        $multimediaObject = $this->removeOwnerUserFromObject($multimediaObject, $user, $executeFlush);
        $series = $this->removeOwnerUserFromObject($multimediaObject->getSeries(), $user, $executeFlush);

        return $multimediaObject;
    }

    private function removeOwnerUserFromObject($object, User $user, $executeFlush=true)
    {
        if (null != $object) {
            $owners = $object->getProperty('owners');
            if (in_array($user->getId(), $owners)) {
                if ($object->isCollection()) {
                    // NOTE: Check all MultimediaObjects from the Series, even the prototype
                    $mmObjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
                    $multimediaObjects = $mmObjRepo->createQueryBuilder()
                      ->field('series')->equals($object);
                    $deleteOwnerInSeries = true;
                    foreach ($multimediaObjects as $multimediaObject) {
                        if (null != $owners = $multimediaObject->getProperty('owners')) {
                            if (in_array($user->getId(), $owners)) {
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

    private function removeUserFromOwnerProperty($object, User $user, $executeFlush=true)
    {
        if (null != $object) {
            $owners = array_filter($object->getProperty('owners'), function ($ownerId) use ($user) {
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

    /**
     * Create user
     *
     * @param User $user
     * @return User
     */
    public function create(User $user)
    {
        if (null != ($permissionProfile = $user->getPermissionProfile())) {
            $user = $this->setUserScope($user, null, $permissionProfile->getScope());
            $user = $this->addRoles($user, $permissionProfile->getPermissions(), false);
        }
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($user);

        return $user;
    }

    /**
     * Update user
     *
     * @param User $user
     * @param boolean $executeFlush
     * @return User
     */
    public function update(User $user, $executeFlush = true)
    {
        if (!$user->isLocal()) {
            throw new \Exception('The user "'.$user->getUsername().'" is not local and can not be modified.');
        }
        $permissionProfile = $user->getPermissionProfile();
        if (null == $permissionProfile) throw new \Exception('The User "'.$user->getUsername().'" has no Permission Profile assigned.');
        /** NOTE: User roles have:
           - ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_USER
           - permission profile roles
           - permission profile scope
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
        $this->dm->persist($user);
        if ($executeFlush) $this->dm->flush();

        $this->dispatcher->dispatchUpdate($user);

        return $user;
    }

    /**
     * Delete user
     *
     * @param User $user
     * @param boolean $executeFlush
     */
    public function delete(User $user, $executeFlush = true)
    {
        $this->dm->remove($user);
        if ($executeFlush) $this->dm->flush();

        $this->dispatcher->dispatchDelete($user);
    }

    /**
     * Add roles
     *
     * @param User $user
     * @paran array $permissions
     * @param boolean $executeFlush
     * @return User
     */
    public function addRoles(User $user, $permissions = array(), $executeFlush = true)
    {
        foreach ($permissions as $permission) {
            if (!$user->hasRole($permission)) {
                $user->addRole($permission);
            }
        }
        $this->dm->persist($user);
        if ($executeFlush) $this->dm->flush();

        return $user;
    }

    /**
     * Remove roles
     *
     * @param User $user
     * @paran array $permissions
     * @param boolean $executeFlush
     * @return User
     */
    public function removeRoles(User $user, $permissions = array(), $executeFlush = true)
    {
        foreach ($permissions as $permission) {
            if ($user->hasRole($permission) && (in_array($permission, array_keys($this->permissionService->getAllPermissions())))) {
                $user->removeRole($permission);
            }
        }
        $this->dm->persist($user);
        if ($executeFlush) $this->dm->flush();

        return $user;
    }

    /**
     * Count Users with given permission profile
     *
     * @param PermissionProfile $permissionProfile
     * @return integer
     */
    public function countUsersWithPermissionProfile(PermissionProfile $permissionProfile)
    {
        return $this->repo->createQueryBuilder()
            ->field('permissionProfile')->references($permissionProfile)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Get Users with given permission profile
     *
     * @param PermissionProfile $permissionProfile
     * @return Cursor
     */
    public function getUsersWithPermissionProfile(PermissionProfile $permissionProfile)
    {
        return $this->repo->createQueryBuilder()
            ->field('permissionProfile')->references($permissionProfile)
            ->getQuery()
            ->execute();
    }

    /**
     * Get user permissions
     *
     * @param array $userRoles
     * @return array $userPermissions
     */
    public function getUserPermissions($userRoles = array())
    {
        $userPermissions = array();
        foreach ($userRoles as $userRole) {
            if (in_array($userRole, array_keys($this->permissionService->getAllPermissions()))) {
                $userPermissions[] = $userRole;
            }
        }

        return $userPermissions;
    }

    /**
     * Set user scope
     *
     * @param User $user
     * @param string $oldScope
     * @param string $newScope
     * @return User
     */
    public function setUserScope(User $user, $oldScope = '', $newScope = '')
    {
        if ($user->hasRole($oldScope)) $user->removeRole($oldScope);
        $user = $this->addUserScope($user, $newScope);

        return $user;
    }

    /**
     * Get user scope
     *
     * @param array $userRoles
     * @return string $userScope
     */
    public function getUserScope($userRoles = array())
    {
        foreach ($userRoles as $userRole) {
            if (in_array($userRole, array_keys(PermissionProfile::$scopeDescription))) {
                return $userRole;
            }
        }

        return null;
    }

    /**
     * Add user scope
     *
     * @param User $user
     * @return User
     */
    public function addUserScope(User $user, $scope= '')
    {
        if ((!$user->hasRole($scope)) &&
            (in_array($scope, array_keys(PermissionProfile::$scopeDescription)))) {
            $user->addRole($scope);
            $this->dm->persist($user);
            $this->dm->flush();
        }

        return $user;
    }

    /**
     * Instantiate User
     *
     * @param  string  $userName
     * @param  string  $email
     * @param  boolean $enabled
     * @return User
     */
    public function instantiate($userName = '', $email = '', $enabled = true)
    {
        $user = new User($this->genUserSalt);
        if ($userName) $user->setUsername($userName);
        if ($email) $user->setEmail($email);
        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null == $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }
        $user->setPermissionProfile($defaultPermissionProfile);
        $user->setEnabled($enabled);
        //$user->setOrigin('cas');

        return $user;
    }

    /**
     * Has Global Scope
     *
     * Checks if the PermissionProfile
     * of the User has Global Scope
     *
     * @param User $user
     * @return boolean
     */
    public function hasGlobalScope(User $user)
    {
        if ($permissionProfile = $user->getPermissionProfile()) {
            return $permissionProfile->isGlobal();
        }

        return false;
    }

    /**
     * Has Personal Scope
     *
     * Checks if the PermissionProfile
     * of the User has Personal Scope
     *
     * @param User $user
     * @return boolean
     */
    public function hasPersonalScope(User $user)
    {
        if ($permissionProfile = $user->getPermissionProfile()) {
            return $permissionProfile->isPersonal();
        }

        return false;
    }

    /**
     * Has None Scope
     *
     * Checks if the PermissionProfile
     * of the User has None Scope
     *
     * @param User $user
     * @return boolean
     */
    public function hasNoneScope(User $user)
    {
        if ($permissionProfile = $user->getPermissionProfile()) {
            return $permissionProfile->isNone();
        }

        return false;
    }

    /**
     * Add group to user
     *
     * @param Group $group
     * @param User $user
     * @param boolean $executeFlush
     */
    public function addGroup(Group $group, User $user, $executeFlush = true)
    {
        if (!$user->containsGroup($group)) {
            if (!$this->isAllowedToModifyUserGroup($user, $group)) {
                throw new \Exception('Not allowed to add group "'.$group->getKey().'" to user "'.$user->getUsername().'".');
            }
            $user->addGroup($group);
            $this->dm->persist($user);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($user);
        }
    }

    /**
     * Delete group from user
     *
     * @param Group $group
     * @param User $user
     * @param boolean $executeFlush
     */
    public function deleteGroup(Group $group, User $user, $executeFlush = true)
    {
        if ($user->containsGroup($group)) {
            if (!$this->isAllowedToModifyUserGroup($user, $group)) {
                throw new \Exception('Not allowed to delete group "'.$group->getKey().'" from user "'.$user->getUsername().'".');
            }
            $user->removeGroup($group);
            $this->dm->persist($user);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($user);
        }
    }

    /**
     * Is allowed to modify group
     *
     * @param User $user
     * @param Group $group
     * @return boolean
     */
    public function isAllowedToModifyUserGroup(User $user, Group $group)
    {
        return ($user->isLocal() && $group->isLocal());
    }

    /**
     * Find with group
     *
     * @param Group
     * @return Cursor
     */
    public function findWithGroup(Group $group)
    {
        return $this->repo->createQueryBuilder()
            ->field('groups')->in(array(new \MongoId($group->getId())))
            ->getQuery()
            ->execute();
    }

    /**
     * Delete all users from group
     *
     * @param  Group
     */
    public function deleteAllFromGroup(Group $group)
    {
        $users = $this->findWithGroup($group);
        foreach ($users as $user) {
            $this->deleteGroup($group, $user, false);
        }
        $this->dm->flush();
    }

    /**
     * Is User last relation
     *
     * @param  User    $user
     * @param  string  $personId
     * @param  array   $owners
     * @param  array   $addGroups
     * @return boolean TRUE if the user is no longer related to multimedia object, FALSE otherwise
     */
    public function isUserLastRelation(User $loggedInUser, $personId = null, $owners = array(), $addGroups = array())
    {
        $personToRemoveIsLogged = $this->isLoggedPersonToRemoveFromOwner($loggedInUser, $personId);
        $userInOwners = $this->isUserInOwners($loggedInUser, $owners);
        $userInAddGroups = $this->isUserInGroups($loggedInUser, $addGroups);

        // Show warning??
        if (($personToRemoveIsLogged && !$userInAddGroups) ||
            (!$personToRemoveIsLogged && !$userInOwners && !$userInAddGroups) ||
            (!$userInOwners && !$userInAddGroups)) {
            return true;
        }

        return false;
    }

    /**
     * Is logged in the person to be removed from owner of a multimedia object
     *
     * @param  User    $loggedInUser
     * @param  string  $personId
     * @return boolean TRUE if person to remove from owner is logged in, FALSE otherwise
     */
    public function isLoggedPersonToRemoveFromOwner(User $loggedInUser, $personId)
    {
        $personToRemove = $this->personRepo->find($personId);
        if ($personToRemove) {
            $userToRemove = $personToRemove->getUser();
            if (!$userToRemove) {
                return false;
            } elseif ($this->hasGlobalScope($userToRemove)) {
                return false;
            } elseif ($loggedInUser === $userToRemove) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is user in owners array
     *
     * @param  User    $user
     * @param  array   $owners
     * @return boolean TRUE if user is in owners array, FALSE otherwise
     */
    public function isUserInOwners(User $loggedInUser, $owners = array())
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

    /**
     * User has group in common with given groups array
     *
     * @param  User    $loggedInUser
     * @param  array   $groups
     * @return boolean TRUE if user has a group in common with the given groups array, FALSE otherwise
     */
    public function isUserInGroups(User $loggedInUser, $groups = array())
    {
        $userInAddGroups = false;
        $userGroups = $loggedInUser->getGroups()->toArray();
        foreach ($groups as $groupString){
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

        return $userInAddGroups;
    }
}