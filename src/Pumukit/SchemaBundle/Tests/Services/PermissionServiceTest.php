<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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

        $this->assertEquals($allPermissions, $permissionService->getAllPermissions());
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
                    'global' => 'ROLE_TWO',
                    'local' => 'ROLE_TWO',
                )
            ),
            array(
                'role' => 'ROLE_TWO',
                'description' => 'Access Two',
                'dependencies' => array(
                    'global' => 'ROLE_THREE',
                    'local' => 'ROLE_THREE',
                )
            ),
            array(
                'role' => 'ROLE_THREE',
                'description' => 'Access Three',
                'dependencies' => array(
                    'global' => 'ROLE_ONE',
                    'local' => 'ROLE_TWO',
                )
            )
        );
    }
}
