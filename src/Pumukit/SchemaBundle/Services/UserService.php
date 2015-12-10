<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\SecurityContext;

class UserService
{
    private $dm;
    private $repo;
    private $securityContext;
    private $autoPublisherDeleteOwners;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     * @param SecurityContext $securityContext
     * @param boolean         $autoPublisherDeleteOwners
     */
    public function __construct(DocumentManager $documentManager, SecurityContext $securityContext, $autoPublisherDeleteOwners=false)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:User');
        $this->securityContext = $securityContext;
        $this->autoPublisherDeleteOwners = $autoPublisherDeleteOwners;
    }

    /**
     * Get logged in user
     */
    public function getLoggedInUser()
    {
        if (null != $token = $this->securityContext->getToken()) {
            return $token->getUser();
        }

        return null;
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
        if (null != $user && null != $multimediaObject) {
            $multimediaObject = $this->addOwnerUserToObject($multimediaObject, $user, $executeFlush);
            $series = $this->addOwnerUserToObject($multimediaObject->getSeries(), $user, $executeFlush);
            if ($executeFlush) {
                $this->dm->flush();
            }
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
        if (null != $object && null != $user) {
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
     * Allow to delete owner
     *
     * Checks if the logged in user
     * is allowed to delete the owner
     * (same user, or another user)
     * from the MultimediaObject
     * or Series
     * Super Admin always is allowed
     *
     * @param User     $userFromPersonToDelete
     * @return boolean
     */
    public function allowToDeleteOwner(User $userFromPersonToDelete)
    {
        if (null != $userFromPersonToDelete && null != $loggedInUser = $this->getLoggedInUser()) {
            if ($userFromPersonToDelete == $loggedInUser) {
                return true;
            }
            if ($loggedInUser->hasRole('ROLE_SUPER_ADMIN')) {
                return true;
            }
            // TODO: Check what happens with ROLE_ADMIN
            if ($loggedInUser->hasRole('ROLE_AUTO_PUBLISHER') && $this->autoPublisherDeleteOwners) {
                return true;
            }
        }

        return false;
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
        if ($user != $this->getLoggedInUser()) {
	    throw new \Exception('Not allowed to remove owner User with id "'.$user->getUsername().'" from MultimediaObject "'.$multimediaObject->getId().'". You are not that User.');
        }

        $multimediaObject = $this->removeOwnerUserFromObject($multimediaObject, $user, $executeFlush);
        $series = $this->removeOwnerUserFromObject($multimediaObject->getSeries(), $user, $executeFlush);

        return $multimediaObject;
    }

    private function removeOwnerUserFromObject($object, User $user, $executeFlush=true)
    {
        if (null != $user && null != $object) {
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

    private function removeUserFromOwnerProperty($object, $user, $executeFlush=true)
    {
        $owners = array_filter($object->getProperty('owners'), function ($ownerId) use ($user) {
            return $ownerId !== $user->getId();
        });
        $object->setProperty('owners', $owners);

        $this->dm->persist($object);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $object;
    }

    /**
     * Is Auto Publisher
     *
     * Checks if the logged in user
     * has role AUTO_PUBLISHER and
     * has not ADMIN privileges
     *
     * @return boolean
     */
    public function isAutoPublisher($user=null)
    {
        if (null == $user) {
            $loggedInUser = $this->getLoggedInUser();
            return $this->checkAutoPublisher($loggedInUser);
        } else {
            return $this->checkAutoPublisher($user);
        }

        return false;
    }

    private function checkAutoPublisher($user = null)
    {
        if (null != $user) {
            if ($user->hasRole('ROLE_AUTO_PUBLISHER') && !$user->hasRole('ROLE_ADMIN')) {
                return true;
            }
        }

        return false;
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

        return $user;
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
            if ($user->hasRole($permission) && (in_array($permission, array_keys(Permission::$permissionDescription)))) {
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
            if (in_array($userRole, array_keys(Permission::$permissionDescription))) {
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
}