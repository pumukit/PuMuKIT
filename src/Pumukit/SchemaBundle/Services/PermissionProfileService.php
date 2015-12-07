<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class PermissionProfileService
{
    private $dm;
    private $repo;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:PermissionProfile');
    }

    /**
     * Update User Permission
     *
     * @param PermissionProfile $permissionProfile
     */
    public function update(PermissionProfile $permissionProfile)
    {
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $default = $this->checkDefault($permissionProfile);

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
     */
    public function checkDefault(PermissionProfile $permissionProfile)
    {
        if ($permissionProfile->isDefault()) {
            $this->repo->changeDefault($permissionProfile);
        }

        $default = $this->repo->findOneByDefault(true);
        if ((null == $default) || (!$default->isDefault())) {
            $default = $this->setDefaultPermissionProfile();
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
     * @return PermissionProfile
     */
    public function addPermission(PermissionProfile $permissionProfile, $permission='')
    {
        if (array_key_exists($permission, Permission::$permissionDescription)) {
            $permissionProfile->addPermission($permission);
            $this->dm->persist($permissionProfile);
            $this->dm->flush();
        }

        return $permissionProfile;
    }

    /**
     * Remove permission
     *
     * @param PermissionProfile $permissionProfile
     * @param string $permission
     * @return PermissionProfile
     */
    public function removePermission(PermissionProfile $permissionProfile, $permission='')
    {
        if ($permissionProfile->containsPermission($permission)) {
            $permissionProfile->removePermission($permission);
            $this->dm->persist($permissionProfile);
            $this->dm->flush();
        }

        return $permissionProfile;
    }
}