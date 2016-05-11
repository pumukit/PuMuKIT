<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;

class UserRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $groupRepo;
    private $factoryService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:User');
        $this->groupRepo = $this->dm->getRepository('PumukitSchemaBundle:Group');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')
            ->remove(array());
        $this->dm->flush();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $user = new User();

        $user->setEmail('test@mail.com');
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testPerson()
    {
        $person = new Person();
        $user = new User();

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $person->setUser($user);
        $user->setPerson($person);

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        $this->assertEquals($person, $user->getPerson());
    }

    public function testPermissionProfile()
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $user = new User();

        $this->dm->persist($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setPermissionProfile($permissionProfile);

        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        $this->assertEquals($permissionProfile, $user->getPermissionProfile());
    }

    public function testUserGroups()
    {
        $this->assertEquals(0, count($this->groupRepo->findAll()));

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $this->assertEquals(1, count($this->groupRepo->findAll()));

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $this->assertEquals(2, count($this->groupRepo->findAll()));

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');
        $user->addAdminGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->containsAdminGroup($group1));
        $this->assertFalse($user->containsAdminGroup($group2));
        $this->assertFalse($user->containsMemberGroup($group1));
        $this->assertFalse($user->containsMemberGroup($group2));
        $this->assertEquals(1, $user->getAdminGroups()->count());
        $this->assertEquals(0, $user->getMemberGroups()->count());

        $user->addAdminGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->containsAdminGroup($group1));
        $this->assertTrue($user->containsAdminGroup($group2));
        $this->assertFalse($user->containsMemberGroup($group1));
        $this->assertFalse($user->containsMemberGroup($group2));
        $this->assertEquals(2, $user->getAdminGroups()->count());
        $this->assertEquals(0, $user->getMemberGroups()->count());

        $user->removeAdminGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($user->containsAdminGroup($group1));
        $this->assertTrue($user->containsAdminGroup($group2));
        $this->assertFalse($user->containsMemberGroup($group1));
        $this->assertFalse($user->containsMemberGroup($group2));
        $this->assertEquals(1, $user->getAdminGroups()->count());
        $this->assertEquals(0, $user->getMemberGroups()->count());

        $this->assertEquals(2, count($this->groupRepo->findAll()));

        $user->addMemberGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($user->containsAdminGroup($group1));
        $this->assertTrue($user->containsAdminGroup($group2));
        $this->assertTrue($user->containsMemberGroup($group1));
        $this->assertFalse($user->containsMemberGroup($group2));
        $this->assertEquals(1, $user->getAdminGroups()->count());
        $this->assertEquals(1, $user->getMemberGroups()->count());

        $this->assertEquals(2, count($this->groupRepo->findAll()));

        $user->removeMemberGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($user->containsAdminGroup($group1));
        $this->assertTrue($user->containsAdminGroup($group2));
        $this->assertFalse($user->containsMemberGroup($group1));
        $this->assertFalse($user->containsMemberGroup($group2));
        $this->assertEquals(1, $user->getAdminGroups()->count());
        $this->assertEquals(0, $user->getMemberGroups()->count());

        $this->assertEquals(2, count($this->groupRepo->findAll()));
    }

    public function testGetGroupsIds()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');
        $user->addAdminGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals(0, count($user->getGroupsIds()));
        $this->assertEquals(1, count($user->getGroupsIds(true)));

        $user->addAdminGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals(0, count($user->getGroupsIds()));
        $this->assertEquals(2, count($user->getGroupsIds(true)));

        $user->addMemberGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals(1, count($user->getGroupsIds()));
        $this->assertEquals(2, count($user->getGroupsIds(true)));
    }

    private function createGroup($key='Group1', $name='Group 1')
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }
}