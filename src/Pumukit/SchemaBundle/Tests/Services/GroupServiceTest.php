<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\GroupEventDispatcherService;

class GroupServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $userRepo;
    private $groupService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:Group');
        $this->userRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:User');

        $dispatcher = new EventDispatcher();
        $groupDispatcher = new GroupEventDispatcherService($dispatcher);

        $this->groupService = new GroupService($this->dm, $groupDispatcher);
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')->remove(array());
        $this->dm->flush();
    }

    public function testCountUsersInGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $user1 = new User();
        $user1->setUsername('test1');
        $user1->setPassword('pass1');
        $user1->setEmail('test1@mail.com');

        $user2 = new User();
        $user2->setUsername('test2');
        $user2->setPassword('pass2');
        $user2->setEmail('test2@mail.com');

        $user3 = new User();
        $user3->setUsername('test3');
        $user3->setPassword('pass3');
        $user3->setEmail('test3@mail.com');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        $this->assertEquals(0, $this->groupService->countUsersInGroup($group1));
        $this->assertEquals(0, $this->groupService->countUsersInGroup($group2));

        $user1->addGroup($group1);
        $this->dm->persist($user1);
        $this->dm->flush();

        $this->assertEquals(1, $this->groupService->countUsersInGroup($group1));
        $this->assertEquals(0, $this->groupService->countUsersInGroup($group2));
    }

    public function testFindUsersInGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $user1 = new User();
        $user1->setUsername('test1');
        $user1->setPassword('pass1');
        $user1->setEmail('test1@mail.com');

        $user2 = new User();
        $user2->setUsername('test2');
        $user2->setPassword('pass2');
        $user2->setEmail('test2@mail.com');

        $user3 = new User();
        $user3->setUsername('test3');
        $user3->setPassword('pass3');
        $user3->setEmail('test3@mail.com');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        $usersGroup1 = $this->groupService->findUsersInGroup($group1)->toArray();
        $usersGroup2 = $this->groupService->findUsersInGroup($group2)->toArray();

        $this->assertFalse(in_array($user1, $usersGroup1));
        $this->assertFalse(in_array($user2, $usersGroup1));
        $this->assertFalse(in_array($user3, $usersGroup1));
        $this->assertFalse(in_array($user1, $usersGroup2));
        $this->assertFalse(in_array($user2, $usersGroup2));
        $this->assertFalse(in_array($user3, $usersGroup2));

        $user1->addGroup($group1);
        $this->dm->persist($user1);
        $this->dm->flush();

        $usersGroup1 = $this->groupService->findUsersInGroup($group1)->toArray();
        $usersGroup2 = $this->groupService->findUsersInGroup($group2)->toArray();

        $this->assertTrue(in_array($user1, $usersGroup1));
        $this->assertFalse(in_array($user2, $usersGroup1));
        $this->assertFalse(in_array($user3, $usersGroup1));
        $this->assertFalse(in_array($user1, $usersGroup2));
        $this->assertFalse(in_array($user2, $usersGroup2));
        $this->assertFalse(in_array($user3, $usersGroup2));
    }

    public function testCreate()
    {
        $this->assertEquals(0, count($this->repo->findAll()));

        $key = 'key';
        $name = 'name';

        $group = new Group();
        $group->setKey($key);
        $group->setName($name);

        $group = $this->groupService->create($group);

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($group, $this->repo->findOneByKey($key));
        $this->assertEquals($group, $this->repo->findOneByName($name));
        $this->assertEquals($group, $this->repo->find($group->getId()));
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  There is already a group created with key
     */
    public function testCreateExceptionKey()
    {
        $key = 'key';
        $name = 'name';

        $group1 = new Group();
        $group1->setKey($key);
        $group1->setName($name);

        $group1 = $this->groupService->create($group1);

        $group2 = new Group();
        $group2->setKey($key);
        $group2->setName($name);

        $group2 = $this->groupService->create($group2);
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  There is already a group created with name
     */
    public function testCreateExceptionName()
    {
        $name = 'name';
        $key1 = 'key1';

        $group1 = new Group();
        $group1->setKey($key1);
        $group1->setName($name);

        $group1 = $this->groupService->create($group1);

        $key2 = 'key2';

        $group2 = new Group();
        $group2->setKey($key2);
        $group2->setName($name);

        $group2 = $this->groupService->create($group2);
    }

    public function testUpdate()
    {
        $this->assertEquals(0, count($this->repo->findAll()));

        $key1 = 'key1';
        $name1 = 'name1';
        $key2 = 'key2';
        $name2 = 'name2';

        $group = new Group();
        $group->setKey($key1);
        $group->setName($name1);

        $group = $this->groupService->create($group);

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($group, $this->repo->findOneByKey($key1));
        $this->assertEquals($group, $this->repo->findOneByName($name1));

        $createdGroup = $this->repo->find($group->getId());

        $this->assertEquals($group, $createdGroup);
        $this->assertEquals($key1, $createdGroup->getKey());
        $this->assertEquals($name1, $createdGroup->getName());
        $this->assertNotEquals($key2, $createdGroup->getKey());
        $this->assertNotEquals($name2, $createdGroup->getName());

        $group->setKey($key2);
        $group->setName($name2);

        $group = $this->groupService->update($group);

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($group, $this->repo->findOneByKey($key2));
        $this->assertEquals($group, $this->repo->findOneByName($name2));

        $updatedGroup = $this->repo->find($group->getId());

        $this->assertEquals($group, $updatedGroup);
        $this->assertNotEquals($key1, $updatedGroup->getKey());
        $this->assertNotEquals($name1, $updatedGroup->getName());
        $this->assertEquals($key2, $updatedGroup->getKey());
        $this->assertEquals($name2, $updatedGroup->getName());
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  There is already a group created with key
     */
    public function testUpdateExceptionKey()
    {
        $key1 = 'key1';
        $name1 = 'name1';

        $group1 = new Group();
        $group1->setKey($key1);
        $group1->setName($name1);

        $key2 = 'key2';
        $name2 = 'name2';

        $group2 = new Group();
        $group2->setKey($key2);
        $group2->setName($name2);

        $group1 = $this->groupService->create($group1);
        $group2 = $this->groupService->create($group2);

        $group1->setKey($key2);

        $group1 = $this->groupService->update($group1);
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  There is already a group created with name
     */
    public function testUpdateExceptionName()
    {
        $key1 = 'key1';
        $name1 = 'name1';

        $group1 = new Group();
        $group1->setKey($key1);
        $group1->setName($name1);

        $key2 = 'key2';
        $name2 = 'name2';

        $group2 = new Group();
        $group2->setKey($key2);
        $group2->setName($name2);

        $group1 = $this->groupService->create($group1);
        $group2 = $this->groupService->create($group2);

        $group1->setName($name2);

        $group1 = $this->groupService->update($group1);
    }

    public function testDelete()
    {
        $this->assertEquals(0, count($this->repo->findAll()));

        $key = 'key';
        $name = 'name';

        $group = new Group();
        $group->setKey($key);
        $group->setName($name);

        $group = $this->groupService->create($group);

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($group, $this->repo->findOneByKey($key));
        $this->assertEquals($group, $this->repo->findOneByName($name));
        $this->assertEquals($group, $this->repo->find($group->getId()));

        $group = $this->groupService->delete($group);

        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testFindById()
    {
        $group = new Group();
        $group->setKey('testing');
        $group->setName('testing@mail.com');
        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertEquals($group, $this->groupService->findById($group->getId()));
    }

    public function testFindAll()
    {
        $group1 = new Group();
        $group1->setKey('testing1');
        $group1->setName('testing1@mail.com');

        $group2 = new Group();
        $group2->setKey('testing2');
        $group2->setName('testing2@mail.com');

        $group3 = new Group();
        $group3->setKey('testing3');
        $group3->setName('testing3@mail.com');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->flush();

        $this->assertEquals(3, count($this->groupService->findAll()));
    }

    public function testFindByIdNotIn()
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $group3 = new Group();
        $group3->setKey('Group3');
        $group3->setName('Group 3');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->flush();

        $ids = array(new \MongoId($group1->getId()), new \MongoId($group3->getId()));
        $groups = $this->groupService->findByIdNotIn($ids)->toArray();
        $this->assertFalse(in_array($group1, $groups));
        $this->assertTrue(in_array($group2, $groups));
        $this->assertFalse(in_array($group3, $groups));
    }
}