<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionProfileService
{
    private $dm;
    private $repo;
    private $dispatcher;
    private $permissionService;

    /**
     * Constructor.
     */
    public function __construct(DocumentManager $documentManager, PermissionProfileEventDispatcherService $dispatcher, PermissionService $permissionService)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(PermissionProfile::class);
        $this->dispatcher = $dispatcher;
        $this->permissionService = $permissionService;
    }

    /**
     * Update User Permission.
     *
     * @param bool $dispatchCreate
     */
    public function update(PermissionProfile $permissionProfile, $dispatchCreate = false)
    {
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->checkDefault($permissionProfile);

        if ($dispatchCreate) {
            $this->dispatcher->dispatchCreate($permissionProfile);
        } else {
            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Check default user permission.
     *
     * Checks if there is any change in 'default' property
     * If there is no PermissionProfile as default,
     * calls setDefaultPermissionProfile.
     *
     * @return PermissionProfile
     */
    public function checkDefault(PermissionProfile $permissionProfile)
    {
        if ($permissionProfile->isDefault()) {
            $default = $this->repo->findOneBy(['default' => true]);
            $this->repo->changeDefault($permissionProfile);
            if (null !== $default) {
                $this->dispatcher->dispatchUpdate($default);
            }
        }

        $default = $this->repo->findOneBy(['default' => true]);
        if ((null === $default) || (!$default->isDefault())) {
            $default = $this->setDefaultPermissionProfile();
            $this->dispatcher->dispatchUpdate($default);
        }

        return $default;
    }

    /**
     * Set default user permission.
     *
     * Set as default user permission
     * the one with less permissions
     *
     * @return PermissionProfile|null
     */
    public function setDefaultPermissionProfile()
    {
        $totalPermissions = count($this->permissionService->getAllPermissions());
        $default = $this->repo->findDefaultCandidate($totalPermissions);

        if (null === $default) {
            return null;
        }

        $default->setDefault(true);
        $this->dm->persist($default);
        $this->dm->flush();

        return $default;
    }

    /**
     * Calls doAddPermission  and the dispatchUpdate event.
     *
     * @param string $permission
     * @param bool   $executeFlush
     *
     * @return PermissionProfile
     */
    public function addPermission(PermissionProfile $permissionProfile, $permission, $executeFlush = true)
    {
        $this->doAddPermission($permissionProfile, $permission, $executeFlush);
        $this->dispatcher->dispatchUpdate($permissionProfile);

        return $permissionProfile;
    }

    /**
     * Adds a permission.
     *
     * @param string $permission
     * @param bool   $executeFlush
     *
     * @return PermissionProfile
     */
    public function doAddPermission(PermissionProfile $permissionProfile, $permission, $executeFlush = true)
    {
        if (array_key_exists($permission, $this->permissionService->getAllPermissions())) {
            $permissionProfile->addPermission($permission);
            foreach ($this->permissionService->getDependenciesByScope($permission, $permissionProfile->getScope()) as $dependency) {
                $permissionProfile->addPermission($dependency);
            }
            $this->dm->persist($permissionProfile);
            if ($executeFlush) {
                $this->dm->flush();
            }
        }

        return $permissionProfile;
    }

    /**
     * Remove permission.
     *
     * @param string $permission
     * @param bool   $executeFlush
     *
     * @return PermissionProfile
     */
    public function removePermission(PermissionProfile $permissionProfile, $permission, $executeFlush = true)
    {
        if ($permissionProfile->containsPermission($permission)) {
            $dependencies = $this->permissionService->getDependablesByScope($permission, $permissionProfile->getScope());
            foreach ($dependencies as $dependency) {
                if ($permissionProfile->containsPermission($dependency)) {
                    throw new \InvalidArgumentException(sprintf('The permission %s cannot be deleted from \'%s\'. The permission %s is ALSO SET and is dependent on %1$s', $permission, $permissionProfile->getName(), $dependency));
                }
            }
            $permissionProfile->removePermission($permission);
            if ($executeFlush) {
                $this->dm->persist($permissionProfile);
            }
            $this->dm->flush();

            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Set scope.
     *
     * @param string $scope
     * @param bool   $executeFlush
     *
     * @return PermissionProfile
     */
    public function setScope(PermissionProfile $permissionProfile, $scope, $executeFlush = true)
    {
        if (array_key_exists($scope, PermissionProfile::$scopeDescription)) {
            $permissionProfile->setScope($scope);
            $this->dm->persist($permissionProfile);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($permissionProfile);
        }

        return $permissionProfile;
    }

    /**
     * Updates all permissions for a given permissionProfile.
     *
     * @param mixed $permissionsList
     * @param mixed $executeFlush
     */
    public function batchUpdate(PermissionProfile $permissionProfile, $permissionsList, $executeFlush = true)
    {
        //Clears all permissions for this permissionProfile.
        $permissionProfile->setPermissions([]);
        foreach ($permissionsList as $permission) {
            $this->doAddPermission($permissionProfile, $permission, false);
        }
        $this->dm->persist($permissionProfile);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchUpdate($permissionProfile);

        return $permissionProfile;
    }

    /**
     * Get Default.
     *
     * @return PermissionProfile|null
     */
    public function getDefault()
    {
        return $this->repo->findOneBy(['default' => true]);
    }

    /**
     * Get by name.
     *
     * @param mixed $name
     *
     * @return PermissionProfile
     */
    public function getByName($name)
    {
        return $this->repo->findOneBy(['name' => $name]);
    }

    public function exportAllToCsv(): string
    {
        $csv = $this->generateCsvHeader();

        return $this->generateCsvContent($csv);
    }

    private function generateCsvHeader(): string
    {
        return implode(';', ['name', 'system', 'default', 'scope', 'permissions']).PHP_EOL;
    }

    private function generateCsvContent(string $csv): string
    {
        $permissionProfiles = $this->dm->getRepository(PermissionProfile::class)->findAll();
        foreach ($permissionProfiles as $permissionProfile) {
            $dataCSV = [];
            $dataCSV[] = $permissionProfile->getName();
            $dataCSV[] = (int) $permissionProfile->getSystem();
            $dataCSV[] = (int) $permissionProfile->getDefault();
            $dataCSV[] = $permissionProfile->getScope();
            $permissions = [];
            foreach ($permissionProfile->getPermissions() as $permission) {
                $permissions[] = $permission;
            }
            $dataPermission = implode(',', $permissions);
            $dataCSV[] = $dataPermission;
            $data = implode(';', $dataCSV);
            $csv .= $data.PHP_EOL;
        }

        return $csv;
    }
}
