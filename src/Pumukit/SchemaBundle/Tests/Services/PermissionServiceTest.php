<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionService;

class PermissionServiceTest extends WebTestCase
{
    private $permissionService;
    private $dm;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();

        $this->permissionService = static::$kernel->getContainer()
          ->get('pumukitschema.permission');
    }

    public function tearDown()
    {
        $this->permissionService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetExternalPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        $this->assertEquals($externalPermissions, $permissionService->getExternalPermissions());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The permission with role 'ROLE_FOUR' is duplicated. Please check the configuration.
     */
    public function testConstructorDuplicatedRoleException()
    {
        $externalPermissions = $this->getExternalPermissions();
        $externalPermissions[] = [
            'role' => 'ROLE_FOUR',
            'description' => 'Access Four',
        ];
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $permissionService->getAllPermissionValues();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Invalid permission: "INVALID_NAME". Permission must start with "ROLE_".
     */
    public function testConstructorRoleNameException()
    {
        $externalPermissions = $this->getExternalPermissions();
        $externalPermissions[] = [
            'role' => 'INVALID_NAME',
            'description' => 'Invalid Name',
        ];
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $permissionService->getAllPermissionValues();
    }

    public function testGetLocalPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        $this->assertEquals(Permission::$permissionDescription, $permissionService->getLocalPermissions());
    }

    public function testGetAllPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        $allPermissions = array_map(function ($a) {
            return $a['description'];
        }, Permission::$permissionDescription);
        $allPermissions['ROLE_ONE'] = 'Access One';
        $allPermissions['ROLE_TWO'] = 'Access Two';
        $allPermissions['ROLE_THREE'] = 'Access Three';
        $allPermissions['ROLE_FOUR'] = 'Access Four';

        $this->assertEquals($allPermissions, $permissionService->getAllPermissions());
    }

    public function testGetAllDependencies()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $allDependencies = array_map(function ($a) {
            return $a['dependencies'];
        }, Permission::$permissionDescription);

        $allDependencies['ROLE_ONE'] = [
            PermissionProfile::SCOPE_GLOBAL => ['ROLE_TWO', 'ROLE_THREE'],
            PermissionProfile::SCOPE_PERSONAL => ['ROLE_TWO'],
        ];
        $allDependencies['ROLE_TWO'] = [
            PermissionProfile::SCOPE_GLOBAL => ['ROLE_THREE', 'ROLE_ONE'],
            PermissionProfile::SCOPE_PERSONAL => [],
        ];
        $allDependencies['ROLE_THREE'] = [
            PermissionProfile::SCOPE_GLOBAL => ['ROLE_ONE', 'ROLE_TWO'],
            PermissionProfile::SCOPE_PERSONAL => [],
        ];
        $allDependencies['ROLE_FOUR'] = [
            PermissionProfile::SCOPE_GLOBAL => [],
            PermissionProfile::SCOPE_PERSONAL => [],
        ];

        $this->assertEquals($allDependencies, $permissionService->getAllDependencies());
    }

    public function testGetDependenciesByScope()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        $this->assertEquals(['ROLE_TWO', 'ROLE_THREE'], $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals(['ROLE_TWO'], $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals(['ROLE_THREE', 'ROLE_ONE'], $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals(['ROLE_ONE', 'ROLE_TWO'], $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[3]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[3]['role'], PermissionProfile::SCOPE_PERSONAL));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The permission with role 'ROLE_DOESNTEXIST' does not exist in the configuration
     */
    public function testGetDependenciesByScopeInvalidPermission()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $dependencies = $permissionService->getDependenciesByScope('ROLE_DOESNTEXIST', PermissionProfile::SCOPE_PERSONAL);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The scope 'NO_SCOPE' is not a valid scope (SCOPE_GLOBAL or SCOPE_PERSONAL)
     */
    public function testGetDependenciesByScopeInvalidScope()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $dependencies = $permissionService->getDependenciesByScope($externalPermissions[3]['role'], 'NO_SCOPE');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The permission with role 'ROLE_DEPENDENCY' does not exist in the configuration
     */
    public function testGetDependenciesByScopeInvalidDependency()
    {
        $erroringPermission = [
            'role' => 'ROLE_BROKEN_DEPENDENCY',
            'description' => 'Access Three',
            'dependencies' => [
                'global' => ['ROLE_ONE', 'ROLE_TWO'],
                'personal' => ['ROLE_DEPENDENCY'],
            ],
        ];
        $externalPermissions = $this->getExternalPermissions();
        $externalPermissions[] = $erroringPermission;
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $permissionService->getAllPermissionValues();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Permission must start with "ROLE_"
     */
    public function testExceptionRole()
    {
        $externalPermissions = [
            [
                'role' => 'NOT_VALID',
                'description' => 'Not valid',
            ],
        ];
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $permissionService->getAllPermissionValues();
    }

    private function getExternalPermissions()
    {
        return [
            [
                'role' => 'ROLE_ONE',
                'description' => 'Access One',
                'dependencies' => [
                    'global' => ['ROLE_TWO'],
                    'personal' => ['ROLE_TWO'],
                ],
            ],
            [
                'role' => 'ROLE_TWO',
                'description' => 'Access Two',
                'dependencies' => [
                    'global' => ['ROLE_THREE'],
                    'personal' => [],
                ],
            ],
            [
                'role' => 'ROLE_THREE',
                'description' => 'Access Three',
                'dependencies' => [
                    'global' => ['ROLE_ONE', 'ROLE_TWO'],
                    'personal' => ['ROLE_THREE'],
                ],
            ],
            [
                'role' => 'ROLE_FOUR',
                'description' => 'Access Four',
            ],
        ];
    }
}
