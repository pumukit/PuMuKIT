<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionService;

class PermissionServiceTest extends WebTestCase
{
    private $permissionService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->permissionService = static::$kernel->getContainer()
          ->get('pumukitschema.permission');
    }

    public function testGetExternalPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($externalPermissions);

        $this->assertEquals($externalPermissions, $permissionService->getExternalPermissions());
    }

    public function testGetLocalPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($externalPermissions);

        $this->assertEquals(Permission::$permissionDescription, $permissionService->getLocalPermissions());
    }

    public function testGetAllPermissions()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($externalPermissions);

        $allPermissions = array_map(function($a){
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
        $permissionService = new PermissionService($externalPermissions);
        $allDependencies = array_map(function($a){
            return $a['dependencies'];
        }, Permission::$permissionDescription);

        $allDependencies['ROLE_ONE'] = array(
            PermissionProfile::SCOPE_GLOBAL => array('ROLE_TWO'),
            PermissionProfile::SCOPE_PERSONAL => array('ROLE_TWO'),
        );
        $allDependencies['ROLE_TWO'] = array(
            PermissionProfile::SCOPE_GLOBAL => array('ROLE_THREE'),
            PermissionProfile::SCOPE_PERSONAL => array(),
        );
        $allDependencies['ROLE_THREE'] = array(
            PermissionProfile::SCOPE_GLOBAL => array('ROLE_ONE','ROLE_TWO'),
            PermissionProfile::SCOPE_PERSONAL => array('ROLE_THREE'),
        );
        $allDependencies['ROLE_FOUR'] = array(
            PermissionProfile::SCOPE_GLOBAL => array(),
            PermissionProfile::SCOPE_PERSONAL => array(),
        );

        $this->assertEquals($allDependencies, $permissionService->getAllDependencies());
    }

    public function testGetDependenciesByScope()
    {
        $externalPermissions = $this->getExternalPermissions();
        $permissionService = new PermissionService($externalPermissions);

        $this->assertEquals(array('ROLE_TWO', 'ROLE_THREE'), $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals(array('ROLE_TWO'), $permissionService->getDependenciesByScope($externalPermissions[0]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals(array('ROLE_THREE', 'ROLE_ONE'), $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals(array(), $permissionService->getDependenciesByScope($externalPermissions[1]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals(array('ROLE_ONE', 'ROLE_TWO'), $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals(array(), $permissionService->getDependenciesByScope($externalPermissions[2]['role'], PermissionProfile::SCOPE_PERSONAL));
        $this->assertEquals(array(), $permissionService->getDependenciesByScope($externalPermissions[3['role'], PermissionProfile::SCOPE_GLOBAL));
        $this->assertEquals(array(), $permissionService->getDependenciesByScope($externalPermissions[3]['role'], PermissionProfile::SCOPE_PERSONAL));

    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Permission must start with "ROLE_"
     */
    public function testExceptionRole()
    {
        $externalPermissions = array(
                                     array(
                                           'role' => 'NOT_VALID',
                                           'description' => 'Not valid'
                                           )
                                     );
        $permissionService = new PermissionService($externalPermissions);
    }

    private function getExternalPermissions()
    {
        return array(
            array(
                'role' => 'ROLE_ONE',
                'description' => 'Access One',
                'dependencies' => array(
                    'global' => array('ROLE_TWO'),
                    'personal' => array('ROLE_TWO'),
                )
            ),
            array(
                'role' => 'ROLE_TWO',
                'description' => 'Access Two',
                'dependencies' => array(
                    'global' => array('ROLE_THREE'),
                    'personal' => array(),
                )
            ),
            array(
                'role' => 'ROLE_THREE',
                'description' => 'Access Three',
                'dependencies' => array(
                    'global' => array('ROLE_ONE', 'ROLE_TWO'),
                    'personal' => array('ROLE_THREE'),
                )
            ),
            array(
                'role' => 'ROLE_FOUR',
                'description' => 'Access Four',
            ),
        );
    }
}
