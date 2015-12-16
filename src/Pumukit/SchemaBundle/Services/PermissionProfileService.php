<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class PermissionProfileService
{
    private $dm;
    private $repo;
    private $dispatcher;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     * @param PermissionProfileEventDispatcherService $dispatcher
     */
    public function __construct(DocumentManager $documentManager, PermissionProfileEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:PermissionProfile');
        $this->dispatcher = $dispatcher;
    }

    /**
     * Update User Permission
     *
     * @param PermissionProfile $permissionProfile
     * @param boolean $dispatchCreate
     */
    public function update(PermissionProfile $permissionProfile, $dispatchCreate = false)
    {
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $default = $this->checkDefault($permissionProfile);

        if ($dispatchCreate) {
            $this->dispatcher->dispatchCreate($permissionProfile);
        } else {
            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Check default user permission
     *
     * Checks if there is any change in 'default' property
     * If there is no PermissionProfile as default,
     * calls setDefaultPermissionProfile.
     *
     * @param PermissionProfile $permissionProfile
     * @return PermissionProfile
     */
    public function checkDefault(PermissionProfile $permissionProfile)
    {
        if ($permissionProfile->isDefault()) {
            $default = $this->repo->findOneByDefault(true);
            $this->repo->changeDefault($permissionProfile);
            if (null != $default) {
                $this->dispatcher->dispatchUpdate($default);
            }
        }

        $default = $this->repo->findOneByDefault(true);
        if ((null == $default) || (!$default->isDefault())) {
            $default = $this->setDefaultPermissionProfile();
            $this->dispatcher->dispatchUpdate($default);
        }

        return $default;
    }

    /**
     * Set default user permission
     *
     * Set as default user permission
     * the one with less permissions
     *
     * @return PermissionProfile
     */
    public function setDefaultPermissionProfile()
    {
        $default = $this->repo->findDefaultCandidate();

        if (null == $default) return false;

        $default->setDefault(true);
        $this->dm->persist($default);
        $this->dm->flush();

        return $default;
    }

    /**
     * Add permission
     *
     * @param PermissionProfile $permissionProfile
     * @param string $permission
     * @param boolean $executeFlush
     * @return PermissionProfile
     */
    public function addPermission(PermissionProfile $permissionProfile, $permission='', $executeFlush=true)
    {
        if (array_key_exists($permission, Permission::$permissionDescription)) {
            $permissionProfile->addPermission($permission);
            $this->dm->persist($permissionProfile);
            if ($executeFlush) $this->dm->flush();

            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Remove permission
     *
     * @param PermissionProfile $permissionProfile
     * @param string $permission
     * @param boolean $executeFlush
     * @return PermissionProfile
     */
    public function removePermission(PermissionProfile $permissionProfile, $permission='', $executeFlush=true)
    {
        if ($permissionProfile->containsPermission($permission)) {
            $permissionProfile->removePermission($permission);
            if ($executeFlush) $this->dm->persist($permissionProfile);
            $this->dm->flush();

            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Set scope
     *
     * @param PermissionProfile $permissionProfile
     * @param string $scope
     * @param boolean $executeFlush
     * @return PermissionProfile
     */
    public function setScope(PermissionProfile $permissionProfile, $scope='', $executeFlush=true)
    {
        if (array_key_exists($scope, PermissionProfile::$scopeDescription)) {
            $permissionProfile->setScope($scope);
            $this->dm->persist($permissionProfile);
            if ($executeFlush) $this->dm->flush();
            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Get Default
     *
     * @return PermissionProfile
     */
    public function getDefault()
    {
        return $this->repo->findOneByDefault(true);
    }
}