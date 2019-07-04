<?php

namespace Pumukit\OpencastBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class ClientServiceTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $trackService;
    private $factoryService;
    private $resourcesDir;
    private $clientService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        if (!static::$kernel->getContainer()->has('pumukitopencast.client')) {
            $this->markTestSkipped('Opencast is not propertly configured.');
        }

        $this->clientService = static::$kernel->getContainer()->get('pumukitopencast.client');
    }

    public function tearDown()
    {
        $this->clientService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetUserRoles()
    {
        $user = new User();

        $user->setRoles(['ROLE_TEST']);
        $out = $this->invokeMethod($this->clientService, 'getUserRoles', [$user]);
        $this->assertEquals('["ROLE_TEST","ROLE_USER"]', $out);

        $user->setRoles(['ROLE_TEST', 'ROLE_TEST_2']);
        $out = $this->invokeMethod($this->clientService, 'getUserRoles', [$user]);
        $this->assertEquals('["ROLE_TEST","ROLE_TEST_2","ROLE_USER"]', $out);

        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $out = $this->invokeMethod($this->clientService, 'getUserRoles', [$user]);
        $this->assertNotEquals('["ROLE_SUPER_ADMIN","ROLE_USER"]', $out);
    }

    public function testGetMediaPackages()
    {
        $this->markTestSkipped(
            'Integration test.'
        );

        $media = $this->clientService->getMediaPackages(0, 0, 0);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
