<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupEventDispatcherService;
use Pumukit\SchemaBundle\Services\GroupService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class GroupServiceTest extends PumukitTestCase
{
    private $repo;

    private $groupService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Group::class);

        $dispatcher = new EventDispatcher();
        $groupDispatcher = new GroupEventDispatcherService($dispatcher);
        $translator = static::$kernel->getContainer()->get('translator');

        $this->groupService = new GroupService($this->dm, $groupDispatcher, $translator);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->groupService = null;
        gc_collect_cycles();
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

        static::assertEquals(0, $this->groupService->countUsersInGroup($group1));
        static::assertEquals(0, $this->groupService->countUsersInGroup($group2));

        $user1->addGroup($group1);
        $this->dm->persist($user1);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countUsersInGroup($group1));
        static::assertEquals(0, $this->groupService->countUsersInGroup($group2));
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

        static::assertNotContains($user1, $usersGroup1);
        static::assertNotContains($user2, $usersGroup1);
        static::assertNotContains($user3, $usersGroup1);
        static::assertNotContains($user1, $usersGroup2);
        static::assertNotContains($user2, $usersGroup2);
        static::assertNotContains($user3, $usersGroup2);

        $user1->addGroup($group1);
        $this->dm->persist($user1);
        $this->dm->flush();

        $usersGroup1 = $this->groupService->findUsersInGroup($group1)->toArray();
        $usersGroup2 = $this->groupService->findUsersInGroup($group2)->toArray();

        static::assertContains($user1, $usersGroup1);
        static::assertNotContains($user2, $usersGroup1);
        static::assertNotContains($user3, $usersGroup1);
        static::assertNotContains($user1, $usersGroup2);
        static::assertNotContains($user2, $usersGroup2);
        static::assertNotContains($user3, $usersGroup2);

        // sort

        $user2->addGroup($group1);
        $user3->addGroup($group1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        $sort1 = ['username' => 1];
        $users1Group1 = $this->groupService->findUsersInGroup($group1, $sort1)->toArray();

        $sort_1 = ['username' => -1];
        $users_1Group1 = $this->groupService->findUsersInGroup($group1, $sort_1)->toArray();

        static::assertEquals([$user1, $user2, $user3], array_values($users1Group1));
        static::assertEquals([$user3, $user2, $user1], array_values($users_1Group1));
    }

    public function testCreate()
    {
        static::assertCount(0, $this->repo->findAll());

        $key = 'key';
        $name = 'name';

        $group = new Group();
        $group->setKey($key);
        $group->setName($name);

        $group = $this->groupService->create($group);

        static::assertCount(1, $this->repo->findAll());
        static::assertEquals($group, $this->repo->findOneBy(['key' => $key]));
        static::assertEquals($group, $this->repo->findOneBy(['name' => $name]));
        static::assertEquals($group, $this->repo->find($group->getId()));
    }

    public function testCreateExceptionKeyAndName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is already a group created with key key and a group created with name name');
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

    public function testCreateExceptionKey()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is already a group created with key');
        $key = 'key';
        $name1 = 'name1';

        $group1 = new Group();
        $group1->setKey($key);
        $group1->setName($name1);

        $group1 = $this->groupService->create($group1);

        $name2 = 'name2';

        $group2 = new Group();
        $group2->setKey($key);
        $group2->setName($name2);

        $group2 = $this->groupService->create($group2);
    }

    public function testCreateExceptionName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is already a group created with name');
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
        static::assertCount(0, $this->repo->findAll());

        $key1 = 'key1';
        $name1 = 'name1';
        $key2 = 'key2';
        $name2 = 'name2';

        $group = new Group();
        $group->setKey($key1);
        $group->setName($name1);

        $group = $this->groupService->create($group);

        static::assertCount(1, $this->repo->findAll());
        static::assertEquals($group, $this->repo->findOneBy(['key' => $key1]));
        static::assertEquals($group, $this->repo->findOneBy(['name' => $name1]));

        $createdGroup = $this->repo->find($group->getId());

        static::assertEquals($group, $createdGroup);
        static::assertEquals($key1, $createdGroup->getKey());
        static::assertEquals($name1, $createdGroup->getName());
        static::assertNotEquals($key2, $createdGroup->getKey());
        static::assertNotEquals($name2, $createdGroup->getName());

        $group->setKey($key2);
        $group->setName($name2);

        $group = $this->groupService->update($group);

        static::assertCount(1, $this->repo->findAll());
        static::assertEquals($group, $this->repo->findOneBy(['key' => $key2]));
        static::assertEquals($group, $this->repo->findOneBy(['name' => $name2]));

        $updatedGroup = $this->repo->find($group->getId());

        static::assertEquals($group, $updatedGroup);
        static::assertNotEquals($key1, $updatedGroup->getKey());
        static::assertNotEquals($name1, $updatedGroup->getName());
        static::assertEquals($key2, $updatedGroup->getKey());
        static::assertEquals($name2, $updatedGroup->getName());
    }

    public function testUpdateExceptionKey()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is already a group created with key');
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

    public function testUpdateExceptionName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is already a group created with name');
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
        static::assertCount(0, $this->repo->findAll());

        $key = 'key';
        $name = 'name';
        $origin = Group::ORIGIN_LOCAL;

        $group = new Group();
        $group->setKey($key);
        $group->setName($name);
        $group->setOrigin($origin);

        $group = $this->groupService->create($group);

        static::assertCount(1, $this->repo->findAll());
        static::assertEquals($group, $this->repo->findOneBy(['key' => $key]));
        static::assertEquals($group, $this->repo->findOneBy(['name' => $name]));
        static::assertEquals($group, $this->repo->find($group->getId()));

        $group = $this->groupService->delete($group);

        static::assertCount(0, $this->repo->findAll());
    }

    public function testDeleteException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not allowed to delete Group "key": is external Group and/or has existent relations with users and multimedia objects.');
        static::assertCount(0, $this->repo->findAll());

        $key = 'key';
        $name = 'name';
        $origin = 'external';

        $group = new Group();
        $group->setKey($key);
        $group->setName($name);
        $group->setOrigin($origin);

        $group = $this->groupService->create($group);

        static::assertCount(1, $this->repo->findAll());

        $group = $this->groupService->delete($group);

        static::assertCount(1, $this->repo->findAll());
    }

    public function testFindById()
    {
        $group = new Group();
        $group->setKey('testing');
        $group->setName('testing@mail.com');
        $this->dm->persist($group);
        $this->dm->flush();

        static::assertEquals($group, $this->groupService->findById($group->getId()));
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

        static::assertCount(3, $this->groupService->findAll());
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

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $groups = $this->groupService->findByIdNotIn($ids)->toArray();
        static::assertNotContains($group1, $groups);
        static::assertContains($group2, $groups);
        static::assertNotContains($group3, $groups);
    }

    public function testFindByIdNotInOf()
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

        $group4 = new Group();
        $group4->setKey('Group4');
        $group4->setName('Group 4');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($group4);
        $this->dm->flush();

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $total = [new ObjectId($group1->getId()), new ObjectId($group3->getId()), new ObjectId($group4->getId())];
        $groups = $this->groupService->findByIdNotInOf($ids, $total)->toArray();
        static::assertNotContains($group1, $groups);
        static::assertNotContains($group2, $groups);
        static::assertNotContains($group3, $groups);
        static::assertContains($group4, $groups);

        $ids = [];
        $total = [new ObjectId($group1->getId()), new ObjectId($group3->getId()), new ObjectId($group4->getId())];
        $groups = $this->groupService->findByIdNotInOf($ids, $total)->toArray();
        static::assertContains($group1, $groups);
        static::assertNotContains($group2, $groups);
        static::assertContains($group3, $groups);
        static::assertContains($group4, $groups);

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $total = [];
        $groups = $this->groupService->findByIdNotInOf($ids, $total)->toArray();
        static::assertNotContains($group1, $groups);
        static::assertNotContains($group2, $groups);
        static::assertNotContains($group3, $groups);
        static::assertNotContains($group4, $groups);

        $ids = [];
        $total = [];
        $groups = $this->groupService->findByIdNotInOf($ids, $total)->toArray();
        static::assertNotContains($group1, $groups);
        static::assertNotContains($group2, $groups);
        static::assertNotContains($group3, $groups);
        static::assertNotContains($group4, $groups);
    }

    public function testCountAdminMultimediaObjectsInGroup()
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');

        $mm1->addGroup($group1);
        $mm1->addGroup($group2);
        $mm2->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countAdminMultimediaObjectsInGroup($group1));
        static::assertEquals(2, $this->groupService->countAdminMultimediaObjectsInGroup($group2));
    }

    public function testCountPlayMultimediaObjectsInGroup()
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        $type = EmbeddedBroadcast::TYPE_GROUPS;
        $name = EmbeddedBroadcast::NAME_GROUPS;

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type);
        $embeddedBroadcast1->setName($name);
        $embeddedBroadcast1->addGroup($group1);
        $embeddedBroadcast1->addGroup($group2);

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type);
        $embeddedBroadcast2->setName($name);
        $embeddedBroadcast2->addGroup($group2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countPlayMultimediaObjectsInGroup($group1));
        static::assertEquals(2, $this->groupService->countPlayMultimediaObjectsInGroup($group2));
    }

    public function testCountResources()
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');

        $mm1->addGroup($group1);
        $mm1->addGroup($group2);
        $mm2->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countAdminMultimediaObjectsInGroup($group1));
        static::assertEquals(2, $this->groupService->countAdminMultimediaObjectsInGroup($group2));

        $type = EmbeddedBroadcast::TYPE_GROUPS;
        $name = EmbeddedBroadcast::NAME_GROUPS;

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type);
        $embeddedBroadcast1->setName($name);
        $embeddedBroadcast1->addGroup($group1);
        $embeddedBroadcast1->addGroup($group2);

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type);
        $embeddedBroadcast2->setName($name);
        $embeddedBroadcast2->addGroup($group2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countPlayMultimediaObjectsInGroup($group1));
        static::assertEquals(2, $this->groupService->countPlayMultimediaObjectsInGroup($group2));

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

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        $user1->addGroup($group1);
        $this->dm->persist($user1);
        $this->dm->flush();

        static::assertEquals(1, $this->groupService->countUsersInGroup($group1));
        static::assertEquals(0, $this->groupService->countUsersInGroup($group2));

        $resourcesInGroup1 = $this->groupService->countResourcesInGroup($group1);
        $resourcesInGroup2 = $this->groupService->countResourcesInGroup($group2);

        static::assertEquals(1, $resourcesInGroup1['adminMultimediaObjects']);
        static::assertEquals(2, $resourcesInGroup2['adminMultimediaObjects']);
        static::assertEquals(1, $resourcesInGroup1['playMultimediaObjects']);
        static::assertEquals(2, $resourcesInGroup2['playMultimediaObjects']);
        static::assertEquals(1, $resourcesInGroup1['users']);
        static::assertEquals(0, $resourcesInGroup2['users']);

        $groups = $this->repo->findAll();
        $resources = $this->groupService->countResources($groups);

        static::assertEquals(1, $resources[$group1->getId()]['adminMultimediaObjects']);
        static::assertEquals(2, $resources[$group2->getId()]['adminMultimediaObjects']);
        static::assertEquals(1, $resources[$group1->getId()]['playMultimediaObjects']);
        static::assertEquals(2, $resources[$group2->getId()]['playMultimediaObjects']);
        static::assertEquals(1, $resources[$group1->getId()]['users']);
        static::assertEquals(0, $resources[$group2->getId()]['users']);
    }

    public function testCanBeDeleted()
    {
        $externalGroup = new Group();
        $externalGroup->setKey('external');
        $externalGroup->setName('external');
        $externalGroup->setOrigin('external');
        $this->dm->persist($externalGroup);
        $this->dm->flush();

        static::assertFalse($this->groupService->canBeDeleted($externalGroup));

        $group = new Group();
        $group->setKey('key1');
        $group->setName('group');
        $group->setOrigin(Group::ORIGIN_LOCAL);

        $this->dm->persist($group);
        $this->dm->flush();

        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');
        $user->addGroup($group);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($this->groupService->canBeDeleted($group));

        $user->removeGroup($group);

        $mm = new MultimediaObject();
        $mm->setNumericalID(1);
        $mm->setTitle('mm');
        $mm->addGroup($group);

        $this->dm->persist($user);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->groupService->canBeDeleted($group));

        $mm->removeGroup($group);
        $embeddedBroadcast = new EmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_GROUPS);
        $embeddedBroadcast->addGroup($group);
        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->groupService->canBeDeleted($group));

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->removeGroup($group);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->groupService->canBeDeleted($group));
    }
}
