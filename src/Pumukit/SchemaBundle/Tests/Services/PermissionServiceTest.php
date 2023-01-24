<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class PermissionServiceTest extends WebTestCase
{
    private $dm;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
    }

    public function tearDown(): void
    {
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetExternalPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        static::assertEquals($externalPermissions, $permissionService->getExternalPermissions());
    }

    public function testConstructorDuplicatedRoleException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The permission with role \'ROLE_FOUR\' is duplicated. Please check the configuration.');
        $externalPermissions = $this->getExternalPermissions();
        $externalPermissions[] = [
            'role' => 'ROLE_FOUR',
            'description' => 'Access Four',
        ];
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $permissionService->getAllPermissionValues();
    }

    public function testConstructorRoleNameException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid permission: "INVALID_NAME". Permission must start with "ROLE_".');
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

        static::assertEquals(Permission::$permissionDescription, $permissionService->getLocalPermissions());
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

        static::assertEquals($allPermissions, $permissionService->getAllPermissions());
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

        static::assertEquals($allDependencies, $permissionService->getAllDependencies());
    }

    public function testGetDependenciesByScope()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);

        static::assertEquals(['ROLE_TWO', 'ROLE_THREE'], $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_GLOBAL));
        static::assertEquals(['ROLE_TWO'], $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_PERSONAL));
        static::assertEquals(['ROLE_THREE', 'ROLE_ONE'], $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_GLOBAL));
        static::assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_PERSONAL));
        static::assertEquals(['ROLE_ONE', 'ROLE_TWO'], $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_GLOBAL));
        static::assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_PERSONAL));
        static::assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[3]['role'], PermissionProfile::SCOPE_GLOBAL));
        static::assertEquals([], $permissionService->getDependenciesByScope($externalPermissions[3]['role'], PermissionProfile::SCOPE_PERSONAL));
    }

    public function testGetDependenciesByScopeInvalidPermission()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The permission with role \'ROLE_DOESNTEXIST\' does not exist in the configuration');
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $dependencies = $permissionService->getDependenciesByScope('ROLE_DOESNTEXIST', PermissionProfile::SCOPE_PERSONAL);
    }

    public function testGetDependenciesByScopeInvalidScope()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The scope \'NO_SCOPE\' is not a valid scope (SCOPE_GLOBAL or SCOPE_PERSONAL)');
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $dependencies = $permissionService->getDependenciesByScope($externalPermissions[3]['role'], 'NO_SCOPE');
    }

    public function testGetDependenciesByScopeInvalidDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The permission with role \'ROLE_DEPENDENCY\' does not exist in the configuration');
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

    public function testExceptionRole()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Permission must start with "ROLE_"');
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
