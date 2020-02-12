<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Role;

/**
 * @internal
 * @coversNothing
 */
class RoleRepositoryTest extends PumukitTestCase
{
    private $repo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Role::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function testRepository()
    {
        $cod = 123;
        $xml = '<xml contenido del xml/>';
        $display = true;
        $name = 'rolename1';
        $text = 'Ahora prueba para ver si coge bien la base de datos';

        $role = new Role();
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name);
        $role->setText($text);

        $this->dm->persist($role);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        $this->assertCount(1, $this->repo->findAll());
    }

    public function testRank()
    {
        $role1 = $this->getRole();

        $this->dm->persist($role1);
        $this->dm->flush();

        $this->assertEquals(0, $role1->getRank());

        $role2 = $this->getRole();

        $this->dm->persist($role2);
        $this->dm->flush();

        $this->assertEquals(1, $role2->getRank());

        $role3 = $this->getRole();
        $role4 = $this->getRole();

        $this->dm->persist($role3);
        $this->dm->persist($role4);
        $this->dm->flush();

        $this->assertEquals(2, $role3->getRank());
        $this->assertEquals(3, $role4->getRank());

        $roleFirst = $this->getRole();
        $roleFirst->setRank(0);

        $this->dm->persist($roleFirst);
        $this->dm->flush();

        $this->assertEquals(0, $roleFirst->getRank());
        $this->assertEquals(1, $role1->getRank());
        $this->assertEquals(4, $role4->getRank());

        $roleLast = $this->getRole();
        $roleLast->setRank(-1);

        $this->dm->persist($roleLast);
        $this->dm->flush();

        $this->assertEquals(0, $roleFirst->getRank());
        $this->assertEquals(1, $role1->getRank());
        $this->assertEquals(4, $role4->getRank());
        $this->assertEquals(5, $roleLast->getRank());

        $role1->setRank(-1);

        $this->dm->persist($role1);
        $this->dm->flush();

        $this->assertEquals(0, $roleFirst->getRank());
        $this->assertEquals(3, $role4->getRank());
        $this->assertEquals(4, $roleLast->getRank());
        $this->assertEquals(5, $role1->getRank());
    }

    private function getRole()
    {
        $rand = rand();

        $cod = $rand;
        $xml = "<xml contenido del xml {$rand} />";
        $name = "rolename{$rand}";
        $text = "text is {$rand}";

        $role = new Role();
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setName($name);
        $role->setText($text);

        return $role;
    }
}
